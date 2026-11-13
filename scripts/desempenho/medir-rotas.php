<?php

/**
 * Mede todas as rotas GET da API: tempo, número de consultas ao banco e tamanho da resposta.
 * Rodar SÓ contra a cópia de teste, dentro da pasta da API:
 *
 *   DB_DATABASE=fastbar_volume CACHE_DRIVER=array php scripts/desempenho/medir-rotas.php | sort -rn | head -40
 *
 * Sem argumento: chama todas as rotas GET sem parâmetro.
 * Com argumentos: chama só os endereços passados (para rotas com parâmetro, pesquisa, per_page etc.):
 *
 *   ... php scripts/desempenho/medir-rotas.php "events/1500" "clients?search=maria&per_page=1000"
 *
 * MEDIR_SQL=1 na frente mostra também as consultas mais repetidas de cada rota.
 *
 * Saída: tempo(ms)  consultas  tamanho(KB)  status  rota
 * Procurar: tempo acima de 300 ms, consultas que crescem com o número de linhas, resposta acima de 500 KB.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$banco = DB::connection()->getDatabaseName();
if (!preg_match('/volume|teste/i', $banco)) {
    fwrite(STDERR, "Banco '$banco' não é cópia de teste (nome precisa ter 'volume' ou 'teste'). Parei.\n");
    exit(1);
}

// Entra como o primeiro usuário administrador (ou o e-mail em MEDIR_EMAIL).
$email = getenv('MEDIR_EMAIL') ?: null;
$user = $email
    ? App\Models\User::where('email', $email)->firstOrFail()
    : App\Models\User::orderBy('id')->firstOrFail();
$token = $user->createToken('medir-rotas')->accessToken;

$pular = '/logout|oauth|callback|webhook|google|export|download|pdf|print|generate|sync|import/i';

$alvos = array_slice($argv, 1);
if (!$alvos) {
    foreach (Route::getRoutes() as $rota) {
        $uri = $rota->uri();
        if (!in_array('GET', $rota->methods(), true)) continue;
        if (!str_starts_with($uri, 'api/')) continue;
        if (str_contains($uri, '{')) continue;
        if (preg_match($pular, $uri)) continue;
        if (str_ends_with($uri, '/create')) continue;
        $alvos[] = substr($uri, 4);
    }
    // Cada rota roda num processo separado: se uma estourar a memória, as outras continuam sendo medidas.
    foreach (array_values(array_unique($alvos)) as $alvo) {
        $saida = [];
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($alvo) . ' 2>&1', $saida, $rc);
        $linha = '';
        foreach ($saida as $l) if (preg_match('/^\s*\d+ ms /', $l)) $linha = $l;
        if ($linha === '') {
            $erro = preg_match('/memory size/i', implode(' ', $saida)) ? 'ESTOUROU A MEMORIA' : 'ERRO (rc ' . $rc . ')';
            $linha = sprintf('%7d ms %5d consultas %8.1f KB  %s  %s', 999999, 0, 0, $erro, $alvo);
        }
        echo $linha, "
";
    }
    exit(0);
}

$consultas = 0;
$repetidas = [];
DB::listen(function ($q) use (&$consultas, &$repetidas) {
    $consultas++;
    $chave = preg_replace('/\s+/', ' ', substr($q->sql, 0, 160));
    $repetidas[$chave] = ($repetidas[$chave] ?? 0) + 1;
});

foreach ($alvos as $alvo) {
    Cache::flush(); // zera o limite de consultas por minuto, senão vira erro 429 e a medição fica falsa
    $consultas = 0;
    $req = Request::create('/api/' . ltrim($alvo, '/'), 'GET');
    $req->headers->set('Authorization', 'Bearer ' . $token);
    $req->headers->set('Accept', 'application/json');

    $t = microtime(true);
    try {
        $resp = $kernel->handle($req);
        $status = $resp->getStatusCode();
        $conteudo = $resp->getContent();
        $kb = $conteudo === false ? 0 : strlen($conteudo) / 1024;
        $kernel->terminate($req, $resp);
    } catch (Throwable $e) {
        $status = 'ERRO ' . substr($e->getMessage(), 0, 80);
        $kb = 0;
    }
    $ms = (microtime(true) - $t) * 1000;

    // MEDIR_MD5=1 acrescenta a "impressão digital" da resposta: prova que uma correção não mudou o resultado.
    $md5 = getenv('MEDIR_MD5') && is_string($conteudo ?? null) ? '  ' . md5($conteudo) : '';
    printf("%7d ms %5d consultas %8.1f KB  %s  %s%s\n", $ms, $consultas, $kb, $status, $alvo, $md5);
    $conteudo = null;
    // MEDIR_SQL=1 mostra as consultas mais repetidas da rota (para achar "uma consulta por linha").
    if (getenv('MEDIR_SQL')) {
        arsort($repetidas);
        foreach (array_slice($repetidas, 0, 8, true) as $sql => $n) {
            fprintf(STDERR, "        %5dx %s\n", $n, $sql);
        }
    }
    $repetidas = [];
    app('auth')->forgetGuards();
}
