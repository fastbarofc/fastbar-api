<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\GoogleAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cria o link do Google Meet sozinho, na conta Google de quem cria a reunião.
 * Cada pessoa conecta o próprio Google uma vez (Minha conta > Conectar Google).
 * O projeto do Google Cloud (ID e senha do cliente OAuth) é colado em Configurações > APIs.
 */
class GoogleMeetService
{
    public const SETTING_CLIENT_ID = 'google_client_id';
    public const SETTING_CLIENT_SECRET = 'google_client_secret';

    private const SCOPES = 'openid email profile https://www.googleapis.com/auth/calendar.events';
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public static function clientId(): ?string
    {
        return env('GOOGLE_CLIENT_ID') ?: AppSetting::getSecret(self::SETTING_CLIENT_ID);
    }

    public static function clientSecret(): ?string
    {
        return env('GOOGLE_CLIENT_SECRET') ?: AppSetting::getSecret(self::SETTING_CLIENT_SECRET);
    }

    public static function isConfigured(): bool
    {
        return (bool) (self::clientId() && self::clientSecret());
    }

    /** Endereço que precisa estar cadastrado no Google Cloud como "URI de redirecionamento autorizado". */
    public static function redirectUri(): string
    {
        return url('/api/google/callback');
    }

    public function authUrl(User $user, string $returnUrl): string
    {
        if (!self::isConfigured()) {
            throw new RuntimeException('O Google ainda não foi configurado. Um administrador precisa informar os códigos em Configurações > APIs.');
        }

        $state = Crypt::encryptString(json_encode([
            'u' => $user->id,
            'r' => $returnUrl,
            't' => now()->timestamp,
        ]));

        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => self::clientId(),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** @return array{user_id:int, return:string} */
    public function readState(string $state): array
    {
        $data = json_decode(Crypt::decryptString($state), true);
        if (!is_array($data) || empty($data['u']) || (now()->timestamp - ($data['t'] ?? 0)) > 1800) {
            throw new RuntimeException('O pedido para conectar o Google expirou. Tente de novo.');
        }

        return ['user_id' => (int) $data['u'], 'return' => (string) ($data['r'] ?? '')];
    }

    public function connect(int $userId, string $code): GoogleAccount
    {
        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        if (!$response->successful()) {
            throw new RuntimeException('O Google não aceitou a conexão. Confira os códigos em Configurações > APIs e tente de novo.');
        }

        $tokens = $response->json();
        $profile = Http::withToken($tokens['access_token'])->timeout(15)
            ->get('https://openidconnect.googleapis.com/v1/userinfo')->json() ?? [];

        $account = GoogleAccount::firstOrNew(['user_id' => $userId]);
        $account->fill([
            'email' => $profile['email'] ?? null,
            'name' => $profile['name'] ?? null,
            'access_token' => $tokens['access_token'],
            'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3500) - 60),
        ]);
        if (!empty($tokens['refresh_token'])) {
            $account->refresh_token = $tokens['refresh_token'];
        }
        $account->save();

        return $account;
    }

    public function disconnect(GoogleAccount $account): void
    {
        $token = $account->refresh_token ?: $account->access_token;
        if ($token) {
            try {
                Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $token]);
            } catch (\Throwable $e) {
                // Mesmo sem conseguir avisar o Google, desconecta aqui
            }
        }
        $account->delete();
    }

    private function accessToken(GoogleAccount $account): string
    {
        if ($account->access_token && $account->expires_at && $account->expires_at->isFuture()) {
            return $account->access_token;
        }
        if (!$account->refresh_token) {
            throw new RuntimeException('A conexão com o Google expirou. Conecte o Google de novo em Minha conta.');
        }

        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);
        if (!$response->successful()) {
            throw new RuntimeException('A conexão com o Google expirou. Conecte o Google de novo em Minha conta.');
        }

        $account->forceFill([
            'access_token' => $response->json('access_token'),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in', 3500) - 60),
        ])->save();

        return $account->access_token;
    }

    /**
     * Cria o evento na Agenda Google da pessoa, já com a sala do Meet.
     * @return array{url:string, event_id:string}
     */
    public function createMeeting(GoogleAccount $account, string $title, Carbon $start, Carbon $end, ?string $description = null): array
    {
        $timezone = config('app.timezone') === 'UTC' ? 'America/Sao_Paulo' : config('app.timezone');

        $response = Http::withToken($this->accessToken($account))->timeout(25)
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', [
                'summary' => $title,
                'description' => $description,
                'start' => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $timezone],
                'end' => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $timezone],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => (string) Str::uuid(),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ],
            ]);

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException('O Google não deixou criar a reunião. Conecte o Google de novo em Minha conta.');
        }
        if (!$response->successful()) {
            throw new RuntimeException('O Google não conseguiu criar a reunião agora. Tente de novo em instantes.');
        }

        $url = $response->json('hangoutLink')
            ?? collect($response->json('conferenceData.entryPoints', []))->firstWhere('entryPointType', 'video')['uri'] ?? null;
        if (!$url) {
            throw new RuntimeException('O Google criou o evento, mas não mandou o link do Meet. Tente de novo.');
        }

        return ['url' => $url, 'event_id' => (string) $response->json('id')];
    }
}
