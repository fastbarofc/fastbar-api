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
        'slug',
        'themed_table',
        'optional',
        'value'
    ];

    protected $casts = [
        'themed_table' => 'boolean',
        'optional' => 'boolean',
    ];

    protected static $sortableFields = [
        'name',
        'themed_table',
        'value',
    ];

    protected static $filterableFields = [
        'name',
        'themed_table',
        'value',
    ];

    protected $appends = ['children'];

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
        return $this->belongsToMany(Product::class, 'kit_product')->withPivot(['value', 'optional']);
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
                    'kit_id' => $item['kit_id'],
                    'name' => $item['name'],
                    'value' => $item['value'],
                    'type' => $item['type']
                ],
                'children' => $getChild ? $item['children'] : (!empty($item['children']) &&  !empty($item['children'][0]) ? [[]] : [])
            ];
        });
    }
}
