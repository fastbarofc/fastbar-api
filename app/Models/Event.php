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

    public function officeEvents()
    {
        return $this->belongsToMany(OfficeEvent::class, 'event_office_event')->withPivot(['quantity']);
    }

    public function eventGifts() {
        return $this->hasMany(EventGift::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'event_material');
    }

    public function extraSales() {
        return $this->hasMany(ExtraSale::class);
    }

    public function eventKits() {
        return $this->hasMany(EventKit::class);
    }

    public function client()
    {
        return $this->belongsToMany(Client::class, 'client_event')->withPivot(['main']);
    }
}
