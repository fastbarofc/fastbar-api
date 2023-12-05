<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Cviebrock\EloquentSluggable\Sluggable;

class Qualification extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sluggable;

    protected $fillable = [
        'description',
        'slug'
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
