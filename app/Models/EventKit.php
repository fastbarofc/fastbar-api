<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class EventKit extends Model implements AuditableInterface
{
    use HasFactory, HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'event_kit_id',
        'event_id',
        'value',
        'themed_table',
        'ordination',
        'observation',
        'optional',
        'checklist',
    ];

    protected $casts = [
        'optional' => 'boolean',
        'themed_table' => 'boolean',
        'checklist' => 'boolean',
    ];

    protected static $sortableFields = [
        'name',
        'value',
        'themed_table',
        'ordination',
        'observation',
    ];

    protected static $filterableFields = [
        'name',
        'themed_table',
        'value',
    ];

    public function event()
    {
        return $this->belongsTo(Kit::class);
    }

    public function eventKit()
    {
        return $this->belongsTo(EventKit::class);
    }

    public function eventKits()
    {
        return $this->hasMany(EventKit::class, 'event_kit_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'event_kit_product')->withPivot(['value', 'ordination', 'optional', 'average consumption ', 'quantity', 'unit', 'comments', 'checklist', 'observation']);
    }

    public function getHasChildrenAttribute()
    {
        return $this->products->isNotEmpty() || $this->eventKits->isNotEmpty();
    }

    public function getChildrenAttribute($getChild = true)
    {
        $products = $this->products;
        $kits = $this->eventKits;

        if (!$products && !$kits) {
            return [];
        }

        if ($products) {
            $products->transform(function ($item) {
                $item['event_kit_id'] = $this->id;
                $item['type'] = 'product';
                $item['checklist'] = $item->pivot->checklist ? true : false;
                $item['optional'] = $item->pivot->optional ? true : false;
                $item['value'] = $item->pivot->value;
                $item['observation'] = $item->pivot->observation;
                $item['ordination'] = $item->pivot->ordination;
                return $item;
            });
        }

        if ($kits) {
            $kits->transform(function ($item) {
                $item['type'] = 'kit';
                return $item;
            });
        }

        if ($products->first()) {
            $children = $products->concat($kits);
        } else {
            $children = $kits;
        }

        if (!$children) {
            return [];
        }

        return $children->map(function ($item) use ($getChild) {
            return [
                'data' => [
                    'id' => $item['id'],
                    'event_kit_id' => $item['event_kit_id'],
                    'name' => $item['name'],
                    'value' => $item['value'],
                    'type' => $item['type'],
                    'themed_table' => $item['themed_table'],
                    'optional' => $item['optional'],
                    'ordination' => $item['ordination'],
                    'checklist' => $item['checklist'],
                    'observation' => $item['observation'],
                ],
                'children' => $getChild ? $item['children'] : ($item['type'] == 'kit' ? [[]] : [])
            ];
        })->sortBy(function ($item) {
            if (is_null($item['data']['ordination'])) {
                return $item['data']['name']; 
            }
            return $item['data']['ordination']; 
        })->values();
    }
}
