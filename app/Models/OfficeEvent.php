<?php

namespace App\Models;

use App\Traits\Sortable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class OfficeEvent extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sluggable, Sortable;

    protected $fillable = [
        'description', 
        'slug',
        'daily',
        'coordinator',
        'date',
    ];

    protected static $sortableFields = [
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

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_office_event')->withPivot(['quantity']);
    }
}
