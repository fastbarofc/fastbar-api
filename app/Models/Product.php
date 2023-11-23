<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Product extends Model implements AuditableInterface
{
    use HasFactory, HasFactory, SoftDeletes, Sluggable, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
        'barcode',
        'unit',
        'weight',
        'manufatturer',
        'value',
        'supplier',
        'marketable',
        'average_consumption',
        'display_checklist',
        'own_production',
        'recipe_yield',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function kits()
    {
        return $this->belongsToMany(kit::class, 'kit_product')->withPivot(['value', 'optional']);
    }
}
