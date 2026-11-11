<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Services\GoogleMeetService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Minha conta > Conectar Google: cada pessoa liga a própria conta Google uma vez
 * para o sistema criar o link do Meet sozinho.
 */
class GoogleController extends Controller
{
    public function __construct(private GoogleMeetService $google)
    {
    }

    public function status(Request $request)
    {
        $account = GoogleAccount::where('user_id', $request->user()->id)->first();

        return response()->json([
            'configured' => GoogleMeetService::isConfigured(),
            'connected' => (bool) $account,
            'email' => $account?->email,
            'name' => $account?->name,
            'redirect_uri' => GoogleMeetService::redirectUri(),
        ], 200);
    }

    public function connectUrl(Request $request)
    {
        $return = (string) $request->query('return', '');
        if (!$this->allowedReturn($return)) {
            return response()->json(['message' => 'Endereço de volta inválido.'], 422);
        }

        try {
            return response()->json(['url' => $this->google->authUrl($request->user(), $return)], 200);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** O Google volta para cá depois que a pessoa autoriza (rota sem login). */
    public function callback(Request $request)
    {
        $return = '';
        try {
            $state = $this->google->readState((string) $request->query('state', ''));
            $return = $state['return'];
            if ($request->query('error')) {
                return redirect()->away($this->withResult($return, 'cancelado'));
            }
            $this->google->connect($state['user_id'], (string) $request->query('code', ''));

            return redirect()->away($this->withResult($return, 'ok'));
        } catch (\Throwable $e) {
            report($e);
            if ($return && $this->allowedReturn($return)) {
                return redirect()->away($this->withResult($return, 'erro'));
            }

            return response('Não foi possível conectar o Google. Volte ao sistema e tente de novo.', 400);
        }
    }

    public function disconnect(Request $request)
    {
        $account = GoogleAccount::where('user_id', $request->user()->id)->first();
        if ($account) {
            $this->google->disconnect($account);
        }

        return $this->status($request);
    }

    private function withResult(string $url, string $result): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . 'google=' . $result;
    }

    /** Só volta para o próprio sistema (computador local, túnel de teste ou o site oficial). */
    private function allowedReturn(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }
        $host = strtolower($parts['host']);

        return in_array($host, ['localhost', '127.0.0.1'], true)
            || $this->isOfficialHost($host)
            || str_ends_with($host, '.trycloudflare.com');
    }

    /** Site oficial do Fastbar: domínios em GOOGLE_RETURN_HOSTS (separados por vírgula), também os subdomínios. */
    private function isOfficialHost(string $host): bool
    {
        $hosts = array_filter(array_map(fn ($h) => strtolower(trim($h)), explode(',', (string) env('GOOGLE_RETURN_HOSTS', 'fastbarbr.com'))));
        foreach ($hosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }
}
