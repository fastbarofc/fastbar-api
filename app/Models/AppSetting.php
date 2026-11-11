<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Configurações gerais do sistema guardadas no banco (chave => valor).
 * Valores sensíveis (ex.: chave da IA) são gravados criptografados.
 */
class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getSecret(string $key): ?string
    {
        $value = static::get($key);
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function putSecret(string $key, ?string $value): void
    {
        static::put($key, $value ? Crypt::encryptString($value) : null);
    }
}
