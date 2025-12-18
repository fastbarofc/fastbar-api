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
    use HasFactory, HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

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
        'recipe_yield',
        'product_group_id',
        'file_id'
    ];

    protected $casts = [
        'marketable' => 'boolean',
        'display_checklist' => 'boolean',
        'own_production' => 'boolean',
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
        'unit_id'
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

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function getPhotoAttribute()
    {
        return $this->file?->path;
    }
}
