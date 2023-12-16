<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class EventStatus extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable;

    protected $fillable = [
        'name',
        'slug'
    ];

    protected static $sortableFields = [
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

}
