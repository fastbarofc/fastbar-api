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

