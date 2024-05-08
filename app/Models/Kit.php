<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Kit extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

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

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

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

    public function getChildrenAttribute() 
    {
        $products = $this->products;
        $products->transform(function ($item) {
            $item['kit_id'] = $this->id;
            $item['type'] = 'product';
            return $item;
        });
        $kits = $this->kits;
        $kits->transform(function ($item) {
            $item['type'] = 'kit';
            return $item;
        });
        $children = $products->merge($kits);
        return $children->map(function ($item) {
            return [
                'id' => $item['id'],
                'kit_id' => $item['kit_id'],
                'type' => $item['type'],
                'name' => $item['name'],
                'themed_table' => $item['themed_table'],
                'optional' => $item['optional'],
                'value' => $item['value'],
                'children' => $item['children']
            ];
        });
    }


}
