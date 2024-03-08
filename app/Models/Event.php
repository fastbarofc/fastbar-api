<?php

namespace App\Models;

use App\Models\Scopes\EventScope;
use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Event extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'slug',
        'client_id',
        'collaborator_id',
        'event_type_id',
        'event_place_id',
        'event_status_id',
        'lease',
        'guests',
        'min_guests',
        'date_time',
        'duration',
        'value_unit',
        'descont_unit',
        'contract_body',
        'courtesy_parthenon',
        'courtesy_partner',
        'courtesies',
    ];

    protected $casts = [
        'date_time' => 'datetime:Y-m-d H:i:s',
    ];

    protected static $sortableFields = [
        'name',
        'guests',
        'lease',
        'date_time',
    ];

    protected static $filterableFields = [
        'name',
        'guests',
        'lease',
        'date_time',
        'event_status_id'
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

    public function client() 
    {
        return $this->belongsTo(Client::class);
    }

    public function collaborator() 
    {
        return $this->belongsTo(Collaborator::class);
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

    public function eventStatus()
    {
        return $this->belongsTo(EventStatus::class);
    }

    public function officeBudgets()
    {
        return $this->belongsToMany(OfficeBudget::class, 'event_office_budget');
    }

    public function utensils()
    {
        return $this->belongsToMany(Utensil::class, 'event_utensil');
    }

}
