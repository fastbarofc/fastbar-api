<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class ExtraSale extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable;

	protected $fillable = [
        'description',
        'slug',
        'unit_id',
        'quantity',
        'value_unit',
        'event_id',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['description']
            ]
        ];
    }

    public function unit() 
    {
        return $this->belongsTo(Unit::class);
    }

    public function event() 
    {
        return $this->belongsTo(Event::class);
    }
}
