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

    /** Chave gravada no evento => como o contrato escreve. */
    public const BAR_COUNTER_TYPES = [
        'lateral' => 'lateral',
        '360' => '360º',
    ];

    /** Chave gravada no evento => como o contrato escreve, depois de "Copos de". */
    public const CUP_TYPES = [
        'acrylic' => 'acrílico',
        'basic_glass' => 'vidro linha básica',
        'premium_glass' => 'vidro linha premium',
    ];

    public const REQUIRED_REGISTRATIONS = [
        'client_id'      => [Client::class, 'O cliente', 'Clientes'],
        'partner_id'     => [Partner::class, 'O parceiro', 'Parceiros'],
        'event_place_id' => [EventPlace::class, 'O local', 'Locais'],
    ];

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
        'glass_cup_hours',
        'guests_per_bartender',
        'bar_counter_type',
        'cup_type',
        'contract_body',
        'contract_print_data',
        'contract_sections',
        'post_event_consumption_report_file_id',
        'parthenon_courtesies',
        'partner_courtesies',
        'unit_insurance',
        'competing_company_id',
        'lost_reason_id',
        'status_reason',
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
        'contract_print_data' => 'array',
        'contract_sections' => 'array',
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


        $query->leftJoin("{$relatedTable}", "{$relatedTable}.id", '=', "{$this->getTable()}.{$foreignKey}")
            ->orderBy("{$relatedTable}.{$field}", $direction);

        // Lista leve (colunas já escolhidas): a junção é por id e não repete
        // eventos, então não precisa agrupar. Senão, mantém como sempre foi.
        if (!empty($query->getQuery()->columns)) {
            return $query;
        }

        return $query->select("{$this->getTable()}.*")->groupBy("{$this->getTable()}.id");
    }

    /**
     * Colunas grandes do evento (texto do contrato). Ficam fora das listas e
     * só são lidas ao abrir o evento ou gerar o contrato.
     */
    public const HEAVY_COLUMNS = ['contract_body', 'contract_sections', 'contract_print_data'];

    private static ?array $lightColumns = null;

    /** Todas as colunas do evento, menos as grandes (usar em listas). */
    public static function lightColumns(): array
    {
        if (self::$lightColumns === null) {
            $table = (new static)->getTable();
            $all = \Illuminate\Support\Facades\Schema::getColumnListing($table);
            self::$lightColumns = array_values(array_map(
                fn ($c) => "{$table}.{$c}",
                array_diff($all, self::HEAVY_COLUMNS)
            ));
        }
        return self::$lightColumns;
    }

    /** Lista leve: o evento sem o texto do contrato. */
    public function scopeLight(Builder $query): Builder
    {
        return $query->select(self::lightColumns());
    }



    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new EventScope());
    }

    /** @param array<string, int> $ids campo de REQUIRED_REGISTRATIONS => id do cadastro a conferir */
    public static function incompleteRegistrationMessages(array $ids): array
    {
        $pending = [];

        foreach ($ids as $field => $id) {
            [$model, $label, $page] = self::REQUIRED_REGISTRATIONS[$field];

            $registration = $model::find($id);
            $missing = $registration?->missingRequiredFields() ?? [];

            if (!empty($missing)) {
                $pending[] = sprintf(
                    '%s "%s" está com cadastro incompleto (%s). Preencha na página de %s.',
                    $label,
                    $registration->name,
                    implode(', ', $missing),
                    $page
                );
            }
        }

        return $pending;
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

    public function addendums()
    {
        return $this->hasMany(EventAddendum::class);
    }

    public function proposals()
    {
        return $this->hasMany(EventProposal::class);
    }

    /** Mesma conta do calculateTotal da aba Valores (event-batches.component.ts no front); mudar uma exige mudar a outra. */
    public function calculatedTotals(): array
    {
        $guests = (int) $this->event_guests;
        $buffet = $guests * ((float) $this->guest_unit_value - (float) $this->unit_discount) + $guests * (float) $this->unit_insurance;
        $subTotal = $buffet + (float) $this->lease - (float) $this->discount;

        $total = $subTotal + (float) $this->addendum_value;
        $totalGuests = $guests + (int) $this->parthenon_courtesies + (int) $this->partner_courtesies;

        foreach ($this->eventBatches as $batch) {
            $total += (int) $batch->guests * (float) $batch->unit_value;
            $totalGuests += (int) $batch->guests;
        }

        if ($totalGuests < (int) $this->present_guests) {
            $total += ((int) $this->present_guests - $totalGuests) * (float) $this->excess_unit_value;
        }

        return [
            'buffet_value' => round($buffet, 2),
            'sub_total' => round($subTotal, 2),
            'total_value' => round($total, 2),
        ];
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

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'event_companies');
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

    public function postEventConsumptionReportFile()
    {
        return $this->belongsTo(File::class, 'post_event_consumption_report_file_id');
    }

    public function paymentPlan()
    {
        return $this->hasOne(PaymentPlan::class);
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }

    public function receivables(): MorphMany
    {
        return $this->morphMany(Receivable::class, 'receivableable');
    }
}
