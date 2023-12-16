<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Kit extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable;

    protected $fillable = [
        'kit_id',
        'name',
        'slug',
        'themed_table',
        'optional',
        'value'
    ];

    protected static $sortableFields = [
        'name',
        'themed_table',
        'value',
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

    public function products()
    {
        return $this->belongsToMany(Product::class, 'kit_product')->withPivot(['value', 'optional']);
    }
}
