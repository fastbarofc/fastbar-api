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

class SectorOffice extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

    protected $fillable = [
        'sector_id', 
        'name',
        'slug'
    ];

    protected static $sortableFields = [
        'name',
        'sector_id'
    ];

    protected static $filterableFields = [
        'name',
        'sector_id'
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
}
