<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Cviebrock\EloquentSluggable\Sluggable;

class Qualification extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sluggable, Sortable, Filterable;

    protected $fillable = [
        'description',
        'slug'
    ];

    protected static $sortableFields = [
        'description',
    ];

    protected static $filterableFields = [
        'description',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['description']
            ]
        ];
    }

    public function collaborators()
    {
        return $this->belongsToMany(Collaborator::class, 'collaborator_qualification')->withPivot(['points']);
    }
}
