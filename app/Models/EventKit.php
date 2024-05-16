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
    ];

    protected $casts = [
        'optional' => 'boolean',
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

    protected $appends = ['children'];

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
        return $this->belongsToMany(Product::class, 'event_kit_product')->withPivot(['event_id', 'value', 'ordination', 'optional', 'average consumption ', 'quantity', 'comments ']);
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
            $children = $products->merge($kits);
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
                    'type' => $item['type']
                ],
                'children' => $getChild ? $item['children'] : (!empty($item['children']) &&  !empty($item['children'][0]) ? [[]] : [])
            ];
        });
    }
}
