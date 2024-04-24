<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class BreakItem extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable, Sluggable;

    protected $fillable = [
        'description',
        'slug',
        'unit_id',
        'value',
        
    ];


    protected static $sortableFields = [
        'name',
        'unit_id',
        'value',
    ];

    protected static $filterableFields = [
        'name',
        'unit_id',
        'value',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['description']
            ]
        ];
    }

    public function unit() {
        return $this->belongsTo(Unit::class);
    }
}
