<?php

namespace App\Models;

use App\Models\Scopes\EventScope;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Event extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable;

    protected $fillable = [
        'name',
        'slug',
        'event_type_id',
        'event_place_id',
        'event_status_id',
        'lease',
        'guests',
        'min_guests',
        'date_time',
        'value_unit',
        'descont_unit',
        'contract_body',
        'courtesy_parthenon',
        'courtesy_partner',
        'courtesies',
    ];

    protected static $sortableFields = [
        'name',
        'guests',
        'lease',
        'date_time',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new EventScope());
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

    public function contracts() {
        return $this->hasMany(Contract::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_event')->withPivot(['main']);
    }


}
