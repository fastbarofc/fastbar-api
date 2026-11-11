<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Focus NFe (focusnfe.com.br): emite, consulta e cancela NFS-e.
 * Cada ambiente tem o seu token (homologação = teste, sem valor fiscal; produção = nota de verdade).
 * Os tokens são colados em Configurações > APIs; o .env tem preferência se estiver preenchido.
 */
class FocusNfeService
{
    public const SETTING_PRODUCTION = 'focus_nfe_token';
    public const SETTING_HOMOLOG = 'focus_nfe_homolog_token';
    public const ENV_PRODUCTION = 'FOCUS_NFE_TOKEN';
    public const ENV_HOMOLOG = 'FOCUS_NFE_HOMOLOG_TOKEN';

    public function __construct(private string $environment = 'homologacao')
    {
    }

    public static function for(string $environment): self
    {
        return new self($environment === 'producao' ? 'producao' : 'homologacao');
    }

    public function baseUrl(): string
    {
        return $this->environment === 'producao' ? 'https://api.focusnfe.com.br' : 'https://homologacao.focusnfe.com.br';
    }

    public function token(): ?string
    {
        if ($this->environment === 'producao') {
            return env(self::ENV_PRODUCTION) ?: AppSetting::getSecret(self::SETTING_PRODUCTION);
        }
        return env(self::ENV_HOMOLOG) ?: AppSetting::getSecret(self::SETTING_HOMOLOG);
    }

    public function configured(): bool
    {
        return (bool) $this->token();
    }

    public function emit(string $ref, array $payload): Response
    {
        return $this->http()->post($this->baseUrl() . '/v2/nfse?ref=' . urlencode($ref), $payload);
    }

    public function consult(string $ref): Response
    {
        return $this->http()->get($this->baseUrl() . '/v2/nfse/' . urlencode($ref));
    }

    public function cancel(string $ref, string $reason): Response
    {
        return $this->http()->delete($this->baseUrl() . '/v2/nfse/' . urlencode($ref), ['justificativa' => $reason]);
    }

    /** Chamada leve só para conferir se o token é aceito (consulta uma referência que não existe). */
    public function ping(): Response
    {
        return $this->http()->get($this->baseUrl() . '/v2/nfse/teste-conexao-fastbar');
    }

    /** Endereço completo de um arquivo devolvido pela Focus (o XML vem como caminho relativo). */
    public function absolute(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        return str_starts_with($path, 'http') ? $path : $this->baseUrl() . $path;
    }

    private function http()
    {
        return Http::timeout(30)->acceptJson()->withBasicAuth((string) $this->token(), '');
    }
}
