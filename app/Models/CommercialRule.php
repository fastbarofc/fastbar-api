<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * Configurações > Regras comerciais: uma única linha com as regras da gerência.
 * Toda alteração fica no Histórico (auditoria) com Antes/Depois.
 */
class CommercialRule extends Model implements AuditableInterface
{
    use Auditable;

    protected $fillable = [
        'high_season_months',
        'viability_min_default',
        'viability_min_by_event_type',
        'max_discount_percent',
        'proposal_validity_days',
        'pre_reserve_days',
        'max_events_per_day',
        'max_guests_per_day',
        'max_events_per_week',
        'complex_max_events_per_day',
        'complex_max_events_per_week',
        'complex_guests_threshold',
        'complex_keywords',
        'max_small_per_weekend',
        'small_keywords',
        'max_pre_events_per_weekend',
        'pre_event_max_guests',
        'pre_event_keywords',
        'first_contact_hours',
        'business_hours_start',
        'business_hours_end',
        'business_days',
        'rotation_user_ids',
        'rotation_event_type_ids',
        'rotation_include_unknown',
        'segments',
        'max_distance_km',
        'no_answer_attempts',
        'no_answer_channels',
        'no_answer_days',
        'stage_max_days',
        'payment_terms',
        'notes',
        'commission_percent_default',
        'updated_by',
    ];

    protected $casts = [
        'high_season_months' => 'array',
        'viability_min_by_event_type' => 'array',
        'stage_max_days' => 'array',
        'business_days' => 'array',
        'rotation_user_ids' => 'array',
        'rotation_event_type_ids' => 'array',
        'rotation_include_unknown' => 'boolean',
        'rotation_last_user_id' => 'integer',
        'segments' => 'array',
        'rotation_pointers' => 'array',
        'max_distance_km' => 'integer',
        'viability_min_default' => 'float',
        'max_discount_percent' => 'float',
        'proposal_validity_days' => 'integer',
        'pre_reserve_days' => 'integer',
        'max_events_per_day' => 'integer',
        'max_guests_per_day' => 'integer',
        'max_events_per_week' => 'integer',
        'complex_max_events_per_day' => 'integer',
        'complex_max_events_per_week' => 'integer',
        'complex_guests_threshold' => 'integer',
        'complex_keywords' => 'array',
        'max_small_per_weekend' => 'integer',
        'small_keywords' => 'array',
        'max_pre_events_per_weekend' => 'integer',
        'pre_event_max_guests' => 'integer',
        'pre_event_keywords' => 'array',
        'first_contact_hours' => 'integer',
        'no_answer_attempts' => 'integer',
        'no_answer_channels' => 'integer',
        'no_answer_days' => 'integer',
    ];

    private static ?self $currentCache = null;
    private static float $currentCacheAt = 0.0;

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCurrent());
        static::deleted(fn () => self::forgetCurrent());
    }

    public static function forgetCurrent(): void
    {
        self::$currentCache = null;
    }

    /**
     * Regras em vigor. Guardadas por até 5 segundos dentro do mesmo pedido, porque as listas do Comercial
     * perguntavam ao banco uma vez por lead. Ao salvar as regras a guarda é zerada.
     */
    public static function current(): self
    {
        if (self::$currentCache !== null && (microtime(true) - self::$currentCacheAt) < 5) {
            return self::$currentCache;
        }
        self::$currentCacheAt = microtime(true);

        return self::$currentCache = static::loadCurrent();
    }

    private static function loadCurrent(): self
    {
        return static::query()->orderBy('id')->first() ?? static::create([
            'high_season_months' => [9, 10, 11, 12],
            'viability_min_default' => 35,
            'viability_min_by_event_type' => [],
            'proposal_validity_days' => 10,
            'pre_reserve_days' => 10,
            'max_events_per_day' => 8,
            'max_guests_per_day' => 6000,
            'max_events_per_week' => 12,
            'complex_max_events_per_day' => 5,
            'complex_max_events_per_week' => 9,
            'complex_guests_threshold' => 2000,
            'complex_keywords' => [],
            'max_small_per_weekend' => 2,
            'small_keywords' => ['fundo de formatura'],
            'max_pre_events_per_weekend' => 2,
            'pre_event_max_guests' => 200,
            'pre_event_keywords' => ['pré-evento', 'pre-evento', 'pré evento', 'pre evento'],
            'first_contact_hours' => 24,
            'business_hours_start' => '08:00',
            'business_hours_end' => '17:00',
            'business_days' => [1, 2, 3, 4, 5],
            'rotation_include_unknown' => true,
            'no_answer_attempts' => Lead::NO_ANSWER_MIN_ATTEMPTS,
            'no_answer_channels' => Lead::NO_ANSWER_MIN_CHANNELS,
            'no_answer_days' => Lead::NO_ANSWER_WINDOW_DAYS,
            'stage_max_days' => Lead::STAGE_MAX_DAYS,
        ]);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Viabilidade mínima (%) para o tipo de evento; sem regra própria usa o padrão. */
    public function viabilityMinFor(?int $eventTypeId): float
    {
        $byType = $this->viability_min_by_event_type ?? [];
        if ($eventTypeId && isset($byType[(string) $eventTypeId]) && $byType[(string) $eventTypeId] !== null && $byType[(string) $eventTypeId] !== '') {
            return (float) $byType[(string) $eventTypeId];
        }

        return (float) ($this->viability_min_default ?? 35);
    }

    public function stageMaxDays(): array
    {
        return array_replace(Lead::STAGE_MAX_DAYS, array_filter($this->stage_max_days ?? [], fn ($v) => $v !== null && $v !== ''));
    }

    /** Segmento comercial (casamentos, corporativos, formaturas) do tipo de evento. */
    public function segmentFor(?int $eventTypeId): ?string
    {
        if (!$eventTypeId) {
            return null;
        }
        foreach ($this->segments ?? [] as $segment) {
            if (in_array($eventTypeId, array_map('intval', $segment['event_type_ids'] ?? []), true)) {
                return $segment['key'];
            }
        }

        return null;
    }

    public function segmentName(?string $key): ?string
    {
        foreach ($this->segments ?? [] as $segment) {
            if ($segment['key'] === $key) {
                return $segment['name'];
            }
        }

        return $key;
    }

    /** Regra do "Sem resposta": tentativas, canais e janela de dias. */
    public function noAnswerRule(): array
    {
        return [
            'attempts' => (int) ($this->no_answer_attempts ?: Lead::NO_ANSWER_MIN_ATTEMPTS),
            'channels' => (int) ($this->no_answer_channels ?: Lead::NO_ANSWER_MIN_CHANNELS),
            'days' => (int) ($this->no_answer_days ?: Lead::NO_ANSWER_WINDOW_DAYS),
        ];
    }

    /** Dias do horário comercial (1 = segunda ... 7 = domingo). */
    public function businessDays(): array
    {
        $days = array_map('intval', $this->business_days ?? []);

        return $days ?: [1, 2, 3, 4, 5];
    }

    /** Minutos do dia em que começa e termina o horário comercial. */
    public function businessWindow(): array
    {
        $toMinutes = function (?string $hhmm, int $fallback): int {
            if (!$hhmm || !preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) {
                return $fallback;
            }

            return min(24 * 60, (int) $m[1] * 60 + (int) $m[2]);
        };
        $start = $toMinutes($this->business_hours_start, 8 * 60);
        $end = $toMinutes($this->business_hours_end, 18 * 60);

        return $end > $start ? [$start, $end] : [8 * 60, 18 * 60];
    }

    /**
     * Classifica um evento/lead para a capacidade: complexo (palavra-chave no nome ou acima do nº de pessoas),
     * pequeno (fundo de formatura) e pré-evento (palavra-chave e abaixo do nº de pessoas).
     */
    public function classify(?string $name, ?int $guests): array
    {
        $text = self::fold((string) $name);
        $has = function (array $words) use ($text): bool {
            foreach ($words as $w) {
                $w = self::fold((string) $w);
                if ($w !== '' && preg_match('/(^|[^a-z0-9])' . preg_quote($w, '/') . '([^a-z0-9]|$)/', $text)) {
                    return true;
                }
            }

            return false;
        };
        $threshold = $this->complex_guests_threshold;
        $preMax = $this->pre_event_max_guests;

        return [
            'complex' => $has($this->complex_keywords ?? []) || ($threshold && $guests !== null && $guests > $threshold),
            'small' => $has($this->small_keywords ?? []),
            'pre_event' => $has($this->pre_event_keywords ?? []) && (!$preMax || $guests === null || $guests < $preMax),
        ];
    }

    /** Minúsculas e sem acento, para comparar palavras-chave. */
    public static function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $ascii !== false ? $ascii : $value;
    }

    public function isHighSeason(?string $date): bool
    {
        if (!$date) {
            return false;
        }

        return in_array((int) date('n', strtotime($date)), array_map('intval', $this->high_season_months ?? []), true);
    }
}
