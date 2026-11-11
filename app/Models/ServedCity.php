<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * Configurações > Cidades atendidas: cidade, consultor responsável e consultor por segmento (tipo de evento).
 * Usada na distribuição automática dos leads.
 */
class ServedCity extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'state',
        'served',
        'min_guests',
        'user_id',
        'segment_rules',
        'notes',
    ];

    protected $casts = [
        'served' => 'boolean',
        'segment_rules' => 'array',
    ];

    protected static $sortableFields = ['name', 'state', 'served'];

    protected static $filterableFields = ['name', 'state'];

    protected static $searchableFields = ['name', 'state'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Consultor para o lead: regra do segmento (tipo de evento) primeiro, depois o padrão da cidade. */
    public function consultantFor(?int $eventTypeId): ?int
    {
        foreach ($this->segment_rules ?? [] as $rule) {
            if ($eventTypeId && (int) ($rule['event_type_id'] ?? 0) === (int) $eventTypeId && !empty($rule['user_id'])) {
                return (int) $rule['user_id'];
            }
        }

        return $this->user_id ? (int) $this->user_id : null;
    }

    /** Procura a cidade pelo nome digitado no lead (sem acento e sem diferenciar maiúsculas). */
    public static function findByName(?string $name): ?self
    {
        $needle = self::normalize($name);
        if ($needle === '') {
            return null;
        }

        return static::query()->get()->first(fn (self $city) => self::normalize($city->name) === $needle);
    }

    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s*[-\/,]\s*[a-z]{2}$/u', '', $value) ?? $value; // tira "- MG"
        return preg_replace('/[^a-z0-9 ]/', '', self::unaccent($value)) ?? '';
    }

    /** Tira acentos sem depender do iconv (no Windows o iconv troca "ó" por "?"). */
    public static function unaccent(string $value): string
    {
        static $map = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'º' => 'o', 'ª' => 'a',
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I',
            'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ú' => 'U', 'Ù' => 'U',
            'Û' => 'U', 'Ü' => 'U', 'Ç' => 'C', 'Ñ' => 'N',
        ];
        $value = strtr($value, $map);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $ascii !== false && $ascii !== '' ? $ascii : $value;
    }
}
