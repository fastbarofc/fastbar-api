<?php

namespace App\Models;

use App\Models\Scopes\EventScope;
use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\File;

class Event extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'old_system_id',
        'client_id',
        'event_id',
        'invoice_client_id',
        'collaborator_id',
        'event_type_id',
        'event_place_id',
        'event_status_id',
        'partner_id',
        'decorator_id',
        'event_capture_id',
        'ceremonial_id',
        'lease',
        'date',
        'time',
        'duration',
        'duration_after',
        'contract_body',
        'post_event_consumption_report_file_id',
        'parthenon_courtesies',
        'partner_courtesies',
        'unit_insurance',
        'competing_company_id',
        'lost_reason_id',
        'is_draft',
        'total_value',
        'buffet_value',
        'sub_total',
        'event_guests',
        'present_guests',
        'guest_unit_value',
        'excess_unit_value',
        'discount',
        'unit_discount',
        'min_guests',
        'is_medicine',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time' => 'string',
        'is_medicine' => 'boolean',
        'closed_at' => 'datetime',
    ];

    protected static $sortableFields = [
        'name',
        'client_id',
        'event_place_id',
        'date',
        'time',
        'email',
        'id',
        'old_system_id'
    ];

    protected static $filterableFields = [
        'id',
        'old_system_id',
        'name',
        'client_id',
        'collaborator_id',
        'event_type_id',
        'email',
        'date',
        'time',
        'event_status_id',
        'event_place_id',
    ];

    protected static $searchableFields = [
        'name',
    ];

    public function scopeOrderByRelation(Builder $query, $relation, $field, $direction = 'asc')
    {
        $relatedModel = $this->{$relation}()->getRelated();

        $relatedTable = $relatedModel->getTable();

        $foreignKey = $this->{$relation}()->getForeignKeyName();


        $query->select("{$this->getTable()}.*")
            ->leftJoin("{$relatedTable}", "{$relatedTable}.id", '=', "{$this->getTable()}.{$foreignKey}")
            ->orderBy("{$relatedTable}.{$field}", $direction);

        return $query->groupBy("{$this->getTable()}.id");
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

    public function invoiceClient()
    {
        return $this->belongsTo(Client::class, 'invoice_client_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function decorator()
    {
        return $this->belongsTo(Decorator::class);
    }

    public function ceremonial()
    {
        return $this->belongsTo(Ceremonial::class);
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

    public function eventGifts()
    {
        return $this->hasMany(EventGift::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'event_material')->withPivot(['quantity', 'selected']);
    }

    public function extraSales()
    {
        return $this->hasMany(ExtraSale::class);
    }

    public function eventKits()
    {
        return $this->hasMany(EventKit::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_event');
    }

    public function getLegalRepresentativesAttribute()
    {
        return $this->clients->where('type', 'pf');
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

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_event', 'event_id', 'event_related_id');
    }

    public function eventNotes()
    {
        return $this->hasMany(EventNote::class);
    }

    public function eventBatches()
    {
        return $this->hasMany(EventBatch::class);
    }

    public function eventStatusHistory()
    {
        return $this->hasMany(EventStatusHistory::class);
    }

    public function histories()
    {
        return $this->hasMany(EventHistory::class);
    }

    public function breakItems()
    {
        return $this->belongsToMany(BreakItem::class, 'event_break_item')->withPivot('quantity', 'value', 'unit_id');
    }

    public function failures()
    {
        return $this->belongsToMany(Failure::class, 'event_failure')->withPivot('deduct');
    }

    public function tastingEvents()
    {
        return $this->belongsToMany(Event::class, 'event_event_tasting', 'event_id', 'related_event_id');
    }

    public function parentEvents()
    {
        return $this->belongsToMany(Event::class, 'event_event_tasting', 'related_event_id', 'event_id');
    }

    public function contractTemplates()
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function postEventConsumptionReportFile()
    {
        return $this->belongsTo(File::class, 'post_event_consumption_report_file_id');
    }

    public function paymentPlan()
    {
        return $this->hasOne(PaymentPlan::class);
    }

    public function receivables(): MorphMany
    {
        return $this->morphMany(Receivable::class, 'receivableable');
    }
}
