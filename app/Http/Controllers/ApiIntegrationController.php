<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações > Integrações > APIs (André, 2026-11-10).
 * Lista os serviços de fora que o Fastbar usa, se estão conectados e para que serve cada um.
 * As chaves ficam criptografadas na tabela app_settings e NUNCA voltam inteiras para a tela
 * (só os 4 últimos caracteres, para a pessoa reconhecer qual chave está salva).
 * Só Administrador acessa (middleware "admin" na rota).
 */
class ApiIntegrationController extends Controller
{
    /** Integrações em que a chave é colada nesta tela: nome do setting => variável do .env que tem preferência. */
    private const KEYS = [
        'anthropic' => ['setting' => 'anthropic_api_key', 'env' => 'ANTHROPIC_API_KEY'],
        'anthropic_admin' => ['setting' => 'anthropic_admin_key', 'env' => 'ANTHROPIC_ADMIN_KEY'],
    ];

    /** Token do WhatsApp/Instagram, gravado pela futura tela Comercial > Comunicação. */
    private const META_TOKEN = 'meta_access_token';

    public function index()
    {
        return response()->json(['data' => $this->list()], 200);
    }

    public function update(Request $request, string $key)
    {
        abort_unless(isset(self::KEYS[$key]), 404);
        $request->validate(
            ['value' => 'required|string|min:10|max:2000'],
            [
                'value.required' => 'Cole a chave.',
                'value.min' => 'Essa chave parece curta demais. Confira se copiou inteira.',
            ]
        );

        $value = trim($request->input('value'));
        $this->putSecret(self::KEYS[$key]['setting'], $value);
        if ($key === 'anthropic_admin') {
            Cache::forget($this->spendCacheKey($value));
        }

        return response()->json(['data' => $this->list()], 200);
    }

    public function destroy(string $key)
    {
        abort_unless(isset(self::KEYS[$key]), 404);
        $this->putSecret(self::KEYS[$key]['setting'], null);

        return response()->json(['data' => $this->list()], 200);
    }

    /** Faz uma chamada simples ao serviço para ver se a chave/conexão funciona. Não gasta crédito. */
    public function test(string $key)
    {
        try {
            [$ok, $message] = match ($key) {
                'anthropic' => $this->testAnthropic(),
                'anthropic_admin' => $this->testAnthropicAdmin(),
                'meta' => $this->testMeta(),
                'viacep' => $this->testViaCep(),
                'open_meteo' => $this->testOpenMeteo(),
                default => abort(404),
            };
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            [$ok, $message] = [false, 'Não foi possível falar com o serviço agora. Confira a internet e tente de novo.'];
        }

        return response()->json(['ok' => $ok, 'message' => $message], 200);
    }

    /**
     * Gasto do Claude no mês atual (US$), pela chave de administrador.
     * Relatório de custos da Anthropic (valores em centavos de dólar), guardado por 5 minutos.
     */
    public function anthropicSpend()
    {
        $adminKey = $this->secret('anthropic_admin');
        if (!$adminKey) {
            return response()->json(['configured' => false], 200);
        }

        $start = now('UTC')->startOfMonth();
        try {
            $cents = Cache::remember($this->spendCacheKey($adminKey), 300, function () use ($adminKey, $start) {
                $total = 0.0;
                $page = null;
                $guard = 0;
                do {
                    // ending_at = amanhã 00h (UTC), para incluir o dia de hoje.
                    $query = [
                        'starting_at' => $start->toIso8601ZuluString(),
                        'ending_at' => now('UTC')->addDay()->startOfDay()->toIso8601ZuluString(),
                        'bucket_width' => '1d',
                        'limit' => 31,
                    ];
                    if ($page) {
                        $query['page'] = $page;
                    }
                    $response = $this->costReport($adminKey, $query);
                    if (!$response->successful()) {
                        throw new \RuntimeException('status ' . $response->status());
                    }
                    foreach ((array) $response->json('data') as $bucket) {
                        foreach ((array) ($bucket['results'] ?? []) as $result) {
                            $total += (float) ($result['amount'] ?? 0);
                        }
                    }
                    $page = $response->json('has_more') ? $response->json('next_page') : null;
                } while ($page && ++$guard < 10);

                return $total;
            });
        } catch (\Throwable $e) {
            report($e);
            $status = preg_match('/status (\d+)/', $e->getMessage(), $m) ? (int) $m[1] : 0;
            $message = match (true) {
                in_array($status, [401, 403], true) => 'A Anthropic recusou a chave do gasto. Ela precisa ser uma chave de administrador da organização.',
                $status === 404 => 'A conta Anthropic ainda não é uma organização. Ative em Settings > Organization e crie a chave de novo.',
                $status > 0 => "A Anthropic não respondeu como esperado (código {$status}). Tente de novo em alguns minutos.",
                default => 'Não foi possível falar com a Anthropic agora. Tente de novo em alguns minutos.',
            };

            return response()->json(['configured' => true, 'ok' => false, 'message' => $message], 200);
        }

        return response()->json([
            'configured' => true,
            'ok' => true,
            'amount_usd' => round($cents / 100, 2),
            'month' => $start->format('Y-m'),
        ], 200);
    }

    /**
     * Evolução dos gastos do Claude: total de cada um dos últimos 12 meses (US$).
     * Usa a mesma chave de administrador do gasto do mês. Guardado por 1 hora.
     */
    public function anthropicSpendHistory()
    {
        $adminKey = $this->secret('anthropic_admin');
        if (!$adminKey) {
            return response()->json(['configured' => false], 200);
        }

        $start = now('UTC')->startOfMonth()->subMonths(11);
        $end = now('UTC')->addDay()->startOfDay();

        try {
            $byMonth = Cache::remember('anthropic_spend_history_' . now('UTC')->format('Y-m-d-H') . '_' . substr(md5($adminKey), 0, 8), 3600, function () use ($adminKey, $start, $end) {
                $totals = [];
                $page = null;
                $guard = 0;
                do {
                    $query = [
                        'starting_at' => $start->toIso8601ZuluString(),
                        'ending_at' => $end->toIso8601ZuluString(),
                        'bucket_width' => '1d',
                        'limit' => 31,
                    ];
                    if ($page) {
                        $query['page'] = $page;
                    }
                    $response = $this->costReport($adminKey, $query);
                    if (!$response->successful()) {
                        throw new \RuntimeException('status ' . $response->status());
                    }
                    foreach ((array) $response->json('data') as $bucket) {
                        $month = \Carbon\Carbon::parse($bucket['starting_at'] ?? 'now')->utc()->format('Y-m');
                        foreach ((array) ($bucket['results'] ?? []) as $result) {
                            $totals[$month] = ($totals[$month] ?? 0) + ((float) ($result['amount'] ?? 0)) / 100;
                        }
                    }
                    $page = $response->json('has_more') ? $response->json('next_page') : null;
                } while ($page && ++$guard < 30);

                return $totals;
            });
        } catch (\Throwable $e) {
            report($e);
            $status = preg_match('/status (\d+)/', $e->getMessage(), $m) ? (int) $m[1] : 0;
            $message = in_array($status, [401, 403], true)
                ? 'A Anthropic recusou a chave de administrador. Confira a linha "gasto do mês" nesta tela.'
                : 'Não foi possível ler os gastos na Anthropic agora. Tente de novo em alguns minutos.';

            return response()->json(['configured' => true, 'ok' => false, 'message' => $message], 200);
        }

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');
            $months[] = ['month' => $key, 'amount_usd' => round($byMonth[$key] ?? 0, 2)];
        }

        return response()->json(['configured' => true, 'ok' => true, 'months' => $months], 200);
    }

    private function list(): array
    {
        $metaOk = (bool) $this->getSecret(self::META_TOKEN);

        return [
            [
                'key' => 'anthropic',
                'name' => 'Claude (Anthropic)',
                'icon' => 'fas fa-robot',
                'purpose' => 'Inteligência artificial do sistema. Vai fazer o resumo dos editais de Licitações no Comercial. Uma chave só serve para tudo.',
                'used_in' => 'Comercial > Licitações',
                'billing' => 'Pago por uso, cobrado no cartão. Não é a mesma coisa que a assinatura do Claude usada para conversar.',
                'site' => 'https://console.anthropic.com/settings/keys',
                'site_label' => 'console.anthropic.com',
                'needs_key' => true,
                'key_label' => 'Chave da API (começa com sk-ant-)',
                ...$this->keyStatus('anthropic'),
            ],
            [
                'key' => 'anthropic_admin',
                'name' => 'Claude: gasto do mês',
                'icon' => 'fas fa-dollar-sign',
                'purpose' => 'Mostra na linha do Claude quanto já foi gasto neste mês. Usa a chave de administrador da conta Anthropic, que só lê os gastos aqui.',
                'used_in' => 'Esta tela (linha do Claude)',
                'billing' => 'Gratuito. A chave de administrador é muito poderosa: guarde com cuidado e nunca mande por mensagem.',
                'site' => 'https://console.anthropic.com/settings/admin-keys',
                'site_label' => 'console.anthropic.com (Admin keys)',
                'needs_key' => true,
                'key_label' => 'Chave de administrador (começa com sk-ant-admin)',
                ...$this->keyStatus('anthropic_admin'),
            ],
            [
                'key' => 'meta',
                'name' => 'WhatsApp e Instagram (Meta)',
                'icon' => 'fab fa-whatsapp',
                'purpose' => 'Vai receber e responder as mensagens do WhatsApp Business e do Instagram dentro do sistema. Entra junto com a tela de Comunicação do Comercial.',
                'used_in' => 'Comercial > Comunicação (em breve)',
                'billing' => 'Conversas cobradas pela Meta conforme o tipo de mensagem; as primeiras de cada mês costumam ser gratuitas.',
                'site' => 'https://developers.facebook.com/apps',
                'site_label' => 'developers.facebook.com',
                'needs_key' => false,
                'connected' => $metaOk,
                'status' => $metaOk ? 'connected' : 'planned',
                'masked' => null,
                'source' => null,
            ],
            [
                'key' => 'viacep',
                'name' => 'ViaCEP',
                'icon' => 'fas fa-map-marker-alt',
                'purpose' => 'Preenche o endereço sozinho quando se digita o CEP nos cadastros.',
                'used_in' => 'Cadastros (clientes, locais, colaboradores...)',
                'billing' => 'Gratuito.',
                'site' => 'https://viacep.com.br',
                'site_label' => 'viacep.com.br',
                'needs_key' => false,
                'connected' => true,
                'status' => 'free',
                'masked' => null,
                'source' => null,
            ],
            [
                'key' => 'open_meteo',
                'name' => 'Open-Meteo',
                'icon' => 'fas fa-cloud-sun',
                'purpose' => 'Mostra a temperatura de Viçosa na tela de Início.',
                'used_in' => 'Início',
                'billing' => 'Gratuito.',
                'site' => 'https://open-meteo.com',
                'site_label' => 'open-meteo.com',
                'needs_key' => false,
                'connected' => true,
                'status' => 'free',
                'masked' => null,
                'source' => null,
            ],
        ];
    }

    /** Situação de uma chave: conectada ou não, de onde vem e os 4 últimos caracteres. */
    private function keyStatus(string $key): array
    {
        $fromEnv = $this->serverValue($key);
        $saved = $this->getSecret(self::KEYS[$key]['setting']);
        $value = $fromEnv ?: $saved;

        return [
            'connected' => (bool) $value,
            'status' => $value ? 'connected' : 'missing',
            'masked' => $value ? '••••••••' . substr($value, -4) : null,
            // "server" = está no arquivo de configuração do servidor (não dá para trocar pela tela).
            'source' => $fromEnv ? 'server' : ($saved ? 'screen' : null),
        ];
    }

    private function secret(string $key): ?string
    {
        return $this->serverValue($key) ?: $this->getSecret(self::KEYS[$key]['setting']);
    }

    /** Chave que está no arquivo de configuração do servidor (.env), que tem preferência sobre a da tela. */
    private function serverValue(string $key): ?string
    {
        return env(self::KEYS[$key]['env']) ?: null;
    }

    private function getSecret(string $setting): ?string
    {
        if (!Schema::hasTable('app_settings')) {
            return null;
        }
        $value = DB::table('app_settings')->where('key', $setting)->value('value');
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function putSecret(string $setting, ?string $value): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => $setting],
            ['value' => $value ? Crypt::encryptString($value) : null, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function spendCacheKey(string $adminKey): string
    {
        return 'anthropic_month_spend_' . now('UTC')->format('Y-m') . '_' . substr(md5($adminKey), 0, 8);
    }

    /**
     * Relatório de custos da Anthropic. Tenta o formato clássico (x-api-key) e, se recusar,
     * o formato com Authorization: Bearer, usado pelas chaves de organização.
     */
    private function costReport(string $key, array $query): \Illuminate\Http\Client\Response
    {
        $url = 'https://api.anthropic.com/v1/organizations/cost_report';
        $response = Http::timeout(15)
            ->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])
            ->get($url, $query);

        if (in_array($response->status(), [401, 403], true)) {
            $retry = Http::timeout(15)
                ->withToken($key)
                ->withHeaders(['anthropic-version' => '2023-06-01'])
                ->get($url, $query);
            if ($retry->successful() || !in_array($retry->status(), [400, 401, 403], true)) {
                return $retry;
            }
        }

        return $response;
    }

    private function testAnthropic(): array
    {
        $value = $this->secret('anthropic');
        if (!$value) {
            return [false, 'A chave do Claude ainda não foi colada.'];
        }
        $response = Http::timeout(15)
            ->withHeaders(['x-api-key' => $value, 'anthropic-version' => '2023-06-01'])
            ->get('https://api.anthropic.com/v1/models');

        return $this->answer($response->status(), 'Claude');
    }

    private function testAnthropicAdmin(): array
    {
        $value = $this->secret('anthropic_admin');
        if (!$value) {
            return [false, 'A chave de administrador ainda não foi colada.'];
        }
        $response = $this->costReport($value, [
            'starting_at' => now('UTC')->startOfMonth()->toIso8601ZuluString(),
            'ending_at' => now('UTC')->addDay()->startOfDay()->toIso8601ZuluString(),
            'bucket_width' => '1d',
            'limit' => 31,
        ]);

        if ($response->successful()) {
            return [true, 'Chave de administrador funcionando. O gasto do mês aparece na linha do Claude.'];
        }
        if (in_array($response->status(), [401, 403], true)) {
            return [false, 'A Anthropic recusou a chave do gasto. Use uma chave de administrador da organização.'];
        }
        if ($response->status() === 404) {
            return [false, 'A conta Anthropic ainda não é uma organização. Ative em Settings > Organization e crie a chave de novo.'];
        }

        return $this->answer($response->status(), 'Claude (administrador)');
    }

    private function testMeta(): array
    {
        $token = $this->getSecret(self::META_TOKEN);
        if (!$token) {
            return [false, 'O WhatsApp/Instagram ainda não foi configurado. Ele entra junto com a tela de Comunicação do Comercial.'];
        }
        $response = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me', ['access_token' => $token]);

        return $this->answer($response->status(), 'Meta');
    }

    private function testViaCep(): array
    {
        $response = Http::timeout(15)->get('https://viacep.com.br/ws/36570000/json/');

        return $response->successful()
            ? [true, 'ViaCEP respondendo normalmente.']
            : [false, 'O ViaCEP não respondeu agora. Tente de novo mais tarde.'];
    }

    private function testOpenMeteo(): array
    {
        $response = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => -20.7546,
            'longitude' => -42.8825,
            'current' => 'temperature_2m',
        ]);

        return $response->successful()
            ? [true, 'Open-Meteo respondendo: agora faz ' . round((float) $response->json('current.temperature_2m')) . '°C em Viçosa.']
            : [false, 'O Open-Meteo não respondeu agora. Tente de novo mais tarde.'];
    }

    private function answer(int $status, string $name): array
    {
        if ($status >= 200 && $status < 300) {
            return [true, "Conectado ao {$name}. A chave está funcionando."];
        }
        if (in_array($status, [401, 403], true)) {
            return [false, "O {$name} recusou a chave. Confira se copiou inteira ou crie uma nova."];
        }

        return [false, "O {$name} não respondeu como esperado (código {$status}). Tente de novo em alguns minutos."];
    }
}
