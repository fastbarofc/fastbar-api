<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use App\Traits\Sortable;
use Cviebrock\EloquentSluggable\Sluggable;

class Utensil extends Model implements AuditableInterface
{
    use HasFactory, Sluggable, SoftDeletes, Auditable, Sortable;

    protected $fillable = [
        'description',
        'slug'
    ];

    protected static $sortableFields = [
        'description'
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
        return $this->belongsToMany(Event::class, 'event_utensil');
    }
}
