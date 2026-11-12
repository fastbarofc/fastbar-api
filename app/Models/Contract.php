<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Contract extends UuidModel implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'event_id',
        'body',
        'version',
        'properties',
        'properties_diff',
        'signed_at',
        'client_id',
        'ip',
    ];

    protected $casts = [
        'properties' => 'array',
        'properties_diff' => 'array',
        'data' => 'array',
    ];

    protected static $sortableFields = [
        'event_id',
        'version',
    ];

    protected static $filterableFields = [
        'event_id',
        'version',
        'client_id'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public static function recordVersion(Event $event, array $properties, ?string $body = null): self
    {
        $last = self::where('event_id', $event->id)->orderByDesc('version')->first();

        return self::create([
            'event_id' => $event->id,
            'client_id' => $event->client_id,
            'version' => ($last?->version ?? 0) + 1,
            'body' => $body,
            'properties' => $properties,
            'properties_diff' => $last ? self::diffProperties($last->properties ?? [], $properties) : [],
        ]);
    }

    /**
     * Histórico (audits): cada versão do contrato já fica guardada nesta tabela, então o histórico
     * não copia o texto nem os dados inteiros. Guarda só o que mudou em relação à versão anterior.
     */
    public function transformAudit(array $data): array
    {
        $extra = null;
        if (($data['event'] ?? null) === 'created' && $this->version > 1) {
            $contract = $this;
            $extra = function () use ($contract) {
                $previous = self::withTrashed()->where('event_id', $contract->event_id)
                    ->where('version', '<', $contract->version)->orderByDesc('version')->value('body');
                $rows = \App\Services\ContractChangeService::rows('body', $previous, $contract->body);

                foreach (($contract->properties_diff['modified'] ?? []) as $key => $change) {
                    if ($key === 'sections' || !is_array($change)) {
                        continue; // o texto das cláusulas já foi comparado acima
                    }
                    $old = $change['old'] ?? null;
                    $new = $change['new'] ?? null;
                    if (is_array($old) || is_array($new)) {
                        continue; // contratante e cardápio aparecem nos próprios cadastros
                    }
                    $rows[] = [
                        'field' => 'properties',
                        'label' => 'Contrato – ' . (ContractTemplate::VARIABLES[$key] ?? ucfirst(str_replace('_', ' ', (string) $key))),
                        'old' => $old === null || $old === '' ? null : (string) $old,
                        'new' => $new === null || $new === '' ? null : (string) $new,
                    ];
                }

                return $rows;
            };
        }

        return \App\Services\ContractChangeService::compactAudit($data, ['body', 'properties', 'properties_diff'], $extra);
    }

    public static function diffProperties(array $old, array $new): array
    {
        $old = self::sortRecursively($old);
        $new = self::sortRecursively($new);

        $modified = [];

        foreach ($old as $key => $value) {
            if (array_key_exists($key, $new) && $new[$key] !== $value) {
                $modified[$key] = ['old' => $value, 'new' => $new[$key]];
            }
        }

        return [
            'added' => array_diff_key($new, $old),
            'removed' => array_diff_key($old, $new),
            'modified' => $modified,
        ];
    }

    private static function sortRecursively(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursively($value);
            }
        }

        ksort($array);

        return $array;
    }
}

