<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Kit extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'kit_id',
        'name',
        'ordination',
        'themed_table',
        'optional',
        'checklist',
        'observation',
    ];

    protected $casts = [
        'optional' => 'boolean',
        'themed_table' => 'boolean',
        'checklist' => 'boolean',
    ];

    protected static $sortableFields = [
        'name',
        'themed_table',
        'optional',
        'checklist',
        'observation',
    ];

    protected static $filterableFields = [
        'name',
        'themed_table',
        'optional',
        'checklist',
    ];

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }

    public function kits()
    {
        return $this->hasMany(Kit::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'kit_product')->withPivot(['value', 'optional', 'ordination', 'checklist', 'observation']);
    }

    public function getHasChildrenAttribute()
    {
        return $this->products->isNotEmpty() || $this->kits->isNotEmpty();
    }

    public function getChildrenAttribute($getChild = true)
    {
        $products = $this->products;
        $kits = $this->kits;

        if (!$products && !$kits) {
            return [];
        }

        if ($products) {
            $products->transform(function ($item) {
                $item['kit_id'] = $this->id;
                $item['type'] = 'product';
                $item['ordination'] = $item->pivot->ordination;
                $item['optional'] = (bool)($item->pivot->optional ?? false);
                $item['checklist'] = (bool)($item->pivot->checklist ?? false);
                $item['observation'] = $item->pivot->observation;
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
            $data = [
                'id' => $item['id'],
                'kit_id' => $item['kit_id'],
                'name' => $item['name'],
                'ordination' => $item['ordination'],
                'type' => $item['type']
            ];

            if ($item['type'] == 'product') {
                $data['optional'] = (bool)($item['optional'] ?? false);
                $data['checklist'] = (bool)($item['checklist'] ?? false);
                $data['observation'] = $item['observation'] ?? null;
            }

            if ($item['type'] == 'kit') {
                $data['themed_table'] = (bool)($item['themed_table'] ?? false);
                $data['optional'] = (bool)($item['optional'] ?? false);
                $data['checklist'] = (bool)($item['checklist'] ?? false);
                $data['observation'] = $item['observation'] ?? null;
            }

            return [
                'data' => $data,
                'children' => $getChild ? $item['children'] : (!empty($item['children']) &&  !empty($item['children'][0]) ? [[]] : [])
            ];
        })->sortBy(function ($item) {
            if (is_null($item['data']['ordination'])) {
                return $item['data']['name'];
            }
            return $item['data']['ordination'];
        })->values();
    }
}
