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

class Sector extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'slug'
    ];

    protected static $sortableFields = [
        'name',
    ];

    protected static $filterableFields = [
        'name',
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

    public function sectorOffices()
    {
        return $this->hasMany(SectorOffice::class);
    }
}
