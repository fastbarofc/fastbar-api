<?php

namespace App\Models;

use App\Traits\Sortable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model implements AuditableInterface
{
    use HasFactory, Sluggable, Auditable, SoftDeletes, Sortable;

    protected $fillable = [
        'description',
        'slug',
        'quantity',
    ];

    protected static $sortableFields = [
        'description',
        'quantity',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['description']
            ]
        ];
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_material');
    }
}
