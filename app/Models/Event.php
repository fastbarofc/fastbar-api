<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Event extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'responsible',
        'event_type_id',
        'event_place_id',
        'lease',
        'guests',
        'min_guests',
        'date_time',
        'value_unit',
        'descont_unit',
        'courtesy_parthenon',
        'courtesy_partner',
        'courtesies',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function eventType() 
    {
        return $this->belongsTo(EventType::class);
    }

    public function eventPlace() 
    {
        return $this->belongsTo(EventPlace::class);
    }
}
