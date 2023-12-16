<?php

namespace App\Models;

use App\Traits\Sortable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventGift extends Model implements AuditableInterface
{
    use HasFactory, Auditable, Sluggable, SoftDeletes, Sortable;

    protected $fillable = [
        'description',
        'slug',
        'event_id',
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
}
