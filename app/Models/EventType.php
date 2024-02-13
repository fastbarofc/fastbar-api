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

class EventType extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'slug',
        'contract_text'
    ];

    protected static $sortableFields = [
        'name'
    ];

    protected static $filterableFields = [
        'name'
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
