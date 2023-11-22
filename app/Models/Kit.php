<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Kit extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable;

    protected $fillable = [
        'kit_id',
        'name',
        'slug',
        'themed_table',
        'optional',
        'value'
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    // criar relacionamento
    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }
}
