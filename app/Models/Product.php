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

class Product extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'barcode',
        'unit_id',
        'weight',
        'manufacturer',
        'value',
        'supplier',
        'marketable',
        'average_consumption',
        'display_checklist',
        'own_production',
        'composition_item',
        'recipe_yield',
        'product_group_id',
        'product_subgroup_id',
        'file_id'
    ];

    protected $casts = [
        'marketable' => 'boolean',
        'display_checklist' => 'boolean',
        'own_production' => 'boolean',
        'composition_item' => 'boolean',
    ];

    protected static $sortableFields = [
        'name',
        'product_group_id',
        'barcode',
        'weight',
        'manufacturer',
        'value',
        'supplier',
    ];

    protected static $filterableFields = [
        'name',
        'product_group_id',
        'barcode',
        'weight',
        'manufacturer',
        'value',
        'supplier',
        'unit_id',
        'composition_item',
        'product_subgroup_id',
        'own_production',
    ];

    protected static $searchableFields = [
        'name',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function kits()
    {
        return $this->belongsToMany(Kit::class, 'kit_product')->withPivot(['value', 'optional', 'ordination', 'checklist', 'observation']);
    }

    public function eventKits()
    {
        return $this->belongsToMany(EventKit::class, 'event_kit_product')->withPivot(['event_id', 'value', 'ordination', 'optional', 'average consumption ', 'quantity', 'comments ']);
    }

    public function productGroup()
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function productSubgroup()
    {
        return $this->belongsTo(ProductSubgroup::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }

    public function components()
    {
        return $this->belongsToMany(Product::class, 'product_compositions', 'product_id', 'component_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function composedProducts()
    {
        return $this->belongsToMany(Product::class, 'product_compositions', 'component_id', 'product_id');
    }

    public function getPhotoAttribute()
    {
        return $this->file?->path;
    }
}
