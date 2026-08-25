<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\ContractResource;
use App\Http\Resources\EventGiftResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventListResource;
use App\Http\Resources\ExtraSaleResource;
use App\Http\Resources\EventKitResource;
use App\Models\Event;
use App\Models\EventGift;
use App\Models\Material;
use App\Models\ExtraSale;
use App\Models\EventKit;
use App\Models\Contract;
use App\Models\EventType;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\EventCalendarResource;
use App\Models\Ceremonial;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\CompetingCompany;
use App\Models\ContractTemplate;
use App\Models\Decorator;
use App\Models\EventBatch;
use App\Models\EventCapture;
use App\Models\EventNote;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\EventStatusHistory;
use App\Models\Failure;
use App\Models\LostReason;
use App\Models\OfficeBudget;
use App\Models\OfficeEvent;
use App\Models\Partner;
use App\Models\PaymentPlan;
use App\Models\Company;

use App\Services\FileService;
use App\Services\EventHistoryService;
use App\Http\Resources\EventHistoryResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use mikehaertl\wkhtmlto\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function __construct(protected EventHistoryService $historyService)
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(EventController::class, 'event');
    }

    public function index(Request $request)
    {
        $query = Event::where('is_draft', false)->with([
            'eventStatus',
            'eventPlace',
            'client',
            'eventType',
            'collaborator',
            'eventKits',
            'companies'
        ]);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);

                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'name' ? 'like' : '=';
                    $isDate = in_array($field, ['date']);

                    if ($field === 'company_id') {
                        $query->whereHas('companies', fn ($q) => $q->where('companies.id', $value));
                    } elseif (str_contains($field, '.')) {
                        [$relation, $relationField] = explode('.', $field);
                        $query->whereHas($relation, function ($q) use ($relationField, $value, $operator) {
                            $q->where($relationField, $operator, $operator === 'like' ? "%$value%" : $value);
                        });
                    } elseif ($field === 'search') {
                        $query->where(function ($q) use ($value) {
                            foreach (Event::searchableFields() as $searchableField) {
                                $q->orWhere('events.' . $searchableField, 'like', "%$value%");
                            }
                        });
                    } else {
                        if ($field === 'id') {
                            $query->where('events.id', $value);
                        } else {
                            $query->filterByField($field, $value, $operator, $isDate);
                        }
                    }
                }
            }
        }

        if ($request->input('order_by')) {
            $sortField = $request->input('order_by');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

            if (str_contains($sortField, '.')) {
                [$relation, $field] = explode('.', $sortField);
                $query->orderByRelation($relation, $field, $direction);
            } else {
                $query->orderBy('events.' . $sortField, $direction);
            }
        }

        $events = $query->paginate($request->input('per_page', 10));

        $eventCompanies = Company::whereIn('internal_name', ['Parthenon', 'Fastbar'])->whereNull('deleted_at')->get(['id', 'internal_name']);

        return response()->json([
            'data' => EventListResource::collection($events),
            'pagination' => [
                'page' => $events->currentPage(),
                'total_pages' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
            'sortable_fields' => Event::sortableFields(),
            'filterable_fields' => Event::filterableFields(),
            'event_types' => EventType::all(['id', 'name']),
            'event_places' => EventPlace::all(['id', 'name']),
            'event_statuses' => EventStatus::all(['id', 'name']),
            'event_companies' => $eventCompanies
        ], 200);
    }





    public function legalRepresentatives(Event $event)
    {
        $this->authorize('legalRepresentatives', $event);
        return response()->json($event->legal_representatives, 200);
    }

    public function indexCalendar(Request $request)
    {
        $disconsideredStatusId = EventStatus::whereIn('slug', ['aberto', 'descartado', 'cancelado'])->get()->pluck('id');

        $query = Event::query()->whereNotIn('event_status_id', $disconsideredStatusId);

        $start = $request->start ? Carbon::parse($request->start)->setHour(0)->setMinute(0)->setSecond(0) : Carbon::now()->startOfWeek(0);
        $end = $request->end ? Carbon::parse($request->end)->setHour(23)->setMinute(59)->setSecond(59) : Carbon::now()->endOfWeek(0);

        $query->whereDate('date', '>=', $start)->whereDate('date', '<', $end);

        // Eager loading de tudo que o EventCalendarResource usa: evita N+1 e
        // permite montar o modal de detalhes sem nenhuma requisicao adicional.
        $query->with([
            'companies',
            'client',
            'eventType',
            'collaborator',
            'ceremonial',
            'eventStatus',
            'eventBatches',
            'eventPlace.address.city.state',
            'eventKits.products',
            'eventNotes.user',
        ]);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    if ($field === 'company_id') {
                        $query->whereHas('companies', fn ($q) => $q->where('companies.id', $value));
                    } else {
                        $operator = $field == 'name' ? 'like' : '=';
                        $query->filterByField($field, $value, $operator);
                    }
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $events = $query->get();

        $eventCompanies = Company::whereIn('internal_name', ['Parthenon', 'Fastbar'])->whereNull('deleted_at')->get(['id', 'internal_name']);

        return response()->json([
            'data' => EventCalendarResource::collection($events),
            'sortable_fields' => Event::sortableFields(),
            'filterable_fields' => Event::filterableFields(),
            'event_companies' => $eventCompanies
        ], 200);
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->all();

        $consumptionReport = $data['post_event_consumption_report'] ?? null;
        $consumptionReportFileName = $data['post_event_consumption_report_file_name'] ?? null;
        $consumptionReportRemove = (bool) ($data['post_event_consumption_report_remove'] ?? false);

        unset(
            $data['post_event_consumption_report'],
            $data['post_event_consumption_report_file_name'],
            $data['post_event_consumption_report_file_id'],
            $data['post_event_consumption_report_file_url'],
            $data['post_event_consumption_report_remove']
        );

        $companyIds = $data['company_ids'] ?? [];
        unset($data['company_ids']);

        $data['collaborator_id'] = auth()->user()->collaborator->id;
        $contractTemplate = null;
        if (!empty($companyIds)) {
            $parthenon = Company::whereIn('id', $companyIds)
                ->where('internal_name', 'Parthenon')
                ->first();
            $companyId = $parthenon?->id ?? $companyIds[0];
            $contractTemplate = ContractTemplate::where('company_id', $companyId)->first();
        }
        $contractTemplate ??= ContractTemplate::first();
        $data['contract_body'] = $contractTemplate?->content ?? null;
        $data['is_medicine'] = $data['is_medicine'] ?? false;

        $event = Event::create($data);


        if ($request->is_draft) {
            return response()->json(new EventResource($event), 201);
        }
        if (!empty($data['event_batches'])) {
            foreach ($data['event_batches'] as $eventBatch) {
                $eventBatch['event_id'] = $event->id;
                EventBatch::create($eventBatch);
            }
        }

        if (!empty($data['materials'])) {
            $materialsArray = collect($data['materials'])->mapWithKeys(function ($material) {
                return [
                    $material['id'] => [
                        'selected' => $material['selected'] === '-1' ? null : $material['selected'],
                        'quantity' => !empty($material['quantity']) ? $material['quantity'] : 0
                    ]
                ];
            })->toArray();
            $event->materials()->sync($materialsArray);
        }

        if (!empty($data['utensils'])) {
            $event->utensils()->sync($data['utensils']);
        }

        if (!empty($data['office_events'])) {
            $officeArray = collect($data['office_events'])->mapWithKeys(function ($office) {
                return [
                    $office['id'] => [
                        'quantity' => !empty($office['quantity']) ? $office['quantity'] : 0
                    ]
                ];
            })->toArray();
            $event->officeEvents()->sync($officeArray);
        }

        if (!empty($data['extra_sales'])) {
            foreach ($data['extra_sales'] as $extraSale) {
                $extraSalesIds = collect($data['extra_sales'])->pluck('id')->toArray();
                $saleToDelete = $event->extraSales->whereNotIn('id', $extraSalesIds);
                foreach ($saleToDelete as $sale) {
                    $sale->delete();
                }
                $extraSaleModel = ExtraSale::find($extraSale['id']);
                if ($extraSaleModel) {
                    $extraSaleModel->update($extraSale);
                    continue;
                }
                $extraSale['event_id'] = $event->id;
                ExtraSale::create($extraSale);
            }
        }

        if (!empty($data['event_notes'])) {
            foreach ($data['event_notes'] as $eventNote) {
                $eventNotesIds = collect($data['event_notes'])->pluck('id')->toArray();
                $eventNotesToDelete = $event->eventNotes->whereNotIn('id', $eventNotesIds);
                foreach ($eventNotesToDelete as $eventNote) {
                    $eventNote->delete();
                }
                $eventNoteModel = EventNote::find($eventNote['id']);
                if ($eventNoteModel) {
                    $eventNoteModel->update($eventNote);
                    continue;
                }
                $eventNote['event_id'] = $event->id;
                $eventNote['collaborator_id'] = auth()->user()->collaborator->id;
                EventNote::create($eventNote);
            }
        }

        if (!empty($data['office_budgets'])) {
            $event->officeBudgets()->sync($data['office_budgets']);
        }


        EventStatusHistory::create(['event_id' => $event->id, 'event_status_id' => $event->event_status_id, 'collaborator_id' => auth()->user()->collaborator->id]);

        if (!empty($companyIds)) {
            $event->companies()->sync($companyIds);
        }

        $this->handleConsumptionReport($event, $consumptionReport, $consumptionReportFileName, $consumptionReportRemove);

        if (!$request->is_draft) {
            $this->historyService->logEventCreation($event);
        }

        $event->load('companies');
        return response()->json(new EventResource($event), 201);
    }

    public function show(Event $event)
    {
        $event->load([
            'client',
            'invoiceClient',
            'collaborator',
            'eventType',
            'eventPlace',
            'eventStatus',
            'companies',
            'materials',
            'officeEvents',
            'eventGifts',
            'extraSales',
            'eventBatches',
            'eventNotes',
            'paymentPlan',
            'paymentPlan.paymentMethod',
            'paymentPlan.installments',
            'receivables',
            'receivables.paymentMethod',
            'postEventConsumptionReportFile',
        ]);

        return response()->json(new EventResource($event), 200);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->refresh();
        $event->load(['officeEvents']);

        $originalData = $event->getAttributes();

        $data = $request->all();

        $originalRelationships = [
            'event_batches' => $event->eventBatches->fresh()->toArray(),
            'event_notes' => $event->eventNotes->fresh()->toArray(),
            'payment_plan' => $event->paymentPlan ? $event->paymentPlan->fresh()->toArray() : null,
            'extra_sales' => $event->extraSales->fresh()->toArray(),
            'event_kits' => $event->eventKits->fresh()->toArray(),
            'materials' => $event->materials()->withPivot(['quantity', 'selected'])->get()->map(function ($material) {
                return [
                    'id' => $material->id,
                    'description' => $material->description,
                    'selected' => $material->pivot->selected === null ? '-1' : (string) $material->pivot->selected,
                    'quantity' => (int) $material->pivot->quantity,
                ];
            })->toArray(),
            'office_events' => $event->officeEvents()->withPivot(['quantity'])->get()->map(function ($office) {
                $quantity = 0;
                if (isset($office->pivot) && isset($office->pivot->quantity)) {
                    $quantity = (int) $office->pivot->quantity;
                }
                return [
                    'id' => $office->id,
                    'description' => $office->description ?? '',
                    'quantity' => $quantity,
                ];
            })->toArray(),
        ];

        $officeEventsWithQuantity = array_filter($originalRelationships['office_events'], function ($item) {
            return (int) ($item['quantity'] ?? 0) > 0;
        });

        $requestOfficeEvents = $data['office_events'] ?? [];
        $requestOfficeEventsWithQuantity = array_filter($requestOfficeEvents, function ($item) {
            return (int) ($item['quantity'] ?? 0) > 0;
        });

        Log::info('EventController@update - Dados originais de office_events capturados', [
            'event_id' => $event->id,
            'office_events_count' => count($originalRelationships['office_events']),
            'office_events_with_quantity_count' => count($officeEventsWithQuantity),
            'office_events_with_quantity' => array_values($officeEventsWithQuantity),
            'request_office_events_count' => count($requestOfficeEvents),
            'request_office_events_with_quantity_count' => count($requestOfficeEventsWithQuantity),
            'request_office_events_with_quantity' => array_values($requestOfficeEventsWithQuantity),
        ]);

        $originalDataWithRelationships = array_merge($originalData, $originalRelationships);

        $consumptionReport = $data['post_event_consumption_report'] ?? null;
        $consumptionReportFileName = $data['post_event_consumption_report_file_name'] ?? null;
        $consumptionReportRemove = (bool) ($data['post_event_consumption_report_remove'] ?? false);

        unset(
            $data['post_event_consumption_report'],
            $data['post_event_consumption_report_file_name'],
            $data['post_event_consumption_report_file_id'],
            $data['post_event_consumption_report_file_url'],
            $data['post_event_consumption_report_remove']
        );

        if (isset($data['event_type_id']) && $data['event_type_id'] !== 7) {
            $data['is_medicine'] = false;
        }

        $data['is_medicine'] = $data['is_medicine'] ?? $event->is_medicine;

        if (isset($data['closed_at'])) {
            $event->closed_at = $data['closed_at'];
        }


        $filteredBatches = isset($data['event_batches']) && is_array($data['event_batches'])
            ? array_filter($data['event_batches'], function ($batch) {
                return isset($batch['guests']) && $batch['guests'] !== null;
            })
            : [];


        if (!empty($filteredBatches)) {
            $batchesIds = collect($filteredBatches)
                ->where('id', '!=', null)
                ->pluck('id')
                ->toArray();

            $batchesToDelete = $event->eventBatches->whereNotIn('id', $batchesIds);
            foreach ($batchesToDelete as $batch) {
                $batch->delete();
            }

            foreach ($filteredBatches as $batch) {
                $batch['event_id'] = $event->id;
                if (!empty($batch['id'])) {
                    EventBatch::find($batch['id'])->update($batch);
                    continue;
                }
                EventBatch::create($batch);
            }
        }



        if (!empty($data['materials'])) {
            $materialsArray = collect($data['materials'])->mapWithKeys(function ($material) {
                return [
                    $material['id'] => [
                        'selected' => $material['selected'] === '-1' ? null : $material['selected'],
                        'quantity' => !empty($material['quantity']) ? $material['quantity'] : 0
                    ]
                ];
            })->toArray();
            $event->materials()->sync($materialsArray);
        }

        if (!empty($data['office_events'])) {
            $uniqueOfficeEvents = [];
            $seenIds = [];
            foreach ($data['office_events'] as $office) {
                $id = $office['id'] ?? null;
                if ($id !== null && !isset($seenIds[$id])) {
                    $seenIds[$id] = true;
                    $uniqueOfficeEvents[] = $office;
                }
            }

            $officeArray = collect($uniqueOfficeEvents)->mapWithKeys(function ($office) {
                return [
                    $office['id'] => [
                        'quantity' => !empty($office['quantity']) ? $office['quantity'] : 0
                    ]
                ];
            })->toArray();

            Log::info('EventController@update - Antes do sync office_events', [
                'event_id' => $event->id,
                'office_array' => $officeArray,
            ]);

            $event->officeEvents()->sync($officeArray);

            $event->refresh();
            $event->load(['officeEvents']);
            $afterSync = $event->officeEvents()->withPivot(['quantity'])->get()->filter(function ($office) {
                return (int) ($office->pivot->quantity ?? 0) > 0;
            })->map(function ($office) {
                return [
                    'id' => $office->id,
                    'description' => $office->description,
                    'quantity' => (int) $office->pivot->quantity,
                ];
            })->values()->toArray();

            Log::info('EventController@update - Após sync office_events', [
                'event_id' => $event->id,
                'after_sync' => $afterSync,
            ]);
        }

        if (!empty($data['extra_sales'])) {
            foreach ($data['extra_sales'] as $extraSale) {
                $extraSalesIds = collect($data['extra_sales'])->pluck('id')->toArray();
                $saleToDelete = $event->extraSales->whereNotIn('id', $extraSalesIds);
                foreach ($saleToDelete as $sale) {
                    $sale->delete();
                }
                $extraSaleModel = ExtraSale::find($extraSale['id']);
                if ($extraSaleModel) {
                    $extraSaleModel->update($extraSale);
                    continue;
                }
                $extraSale['event_id'] = $event->id;
                ExtraSale::create($extraSale);
            }
        }

        if (!empty($data['event_notes'])) {
            foreach ($data['event_notes'] as $eventNote) {
                $eventNotesIds = collect($data['event_notes'])->pluck('id')->toArray();
                $eventNotesToDelete = $event->eventNotes->whereNotIn('id', $eventNotesIds);
                foreach ($eventNotesToDelete as $eventNote) {
                    $eventNote->delete();
                }
                $eventNoteModel = EventNote::find($eventNote['id']);
                if ($eventNoteModel) {
                    $eventNoteModel->update($eventNote);
                    continue;
                }
                $eventNote['event_id'] = $event->id;
                $eventNote['collaborator_id'] = auth()->user()->collaborator->id;
                EventNote::create($eventNote);
            }
        }

        if (!empty($data['related_tasting_events'])) {
            $relatedTastingEvents = $data['related_tasting_events'];
            $event->tastingEvents()->sync($relatedTastingEvents);

            $tastingEvents = Event::findMany($relatedTastingEvents);
            foreach ($tastingEvents as $tastingEvent) {
                $tastingEvent->parentEvents()->syncWithoutDetaching([$event->id]);
            }
        }

        if (!empty($data['parent_events'])) {
            $parentEvents = $data['parent_events'];
            $event->parentEvents()->sync($parentEvents);

            $mainEvents = Event::findMany($parentEvents);
            foreach ($mainEvents as $mainEvent) {
                $mainEvent->tastingEvents()->syncWithoutDetaching([$event->id]);
            }
        }


        if (!empty($data['payment_plan'])) {

            $paymentPlanData = $data['payment_plan'];

            if ($this->isPaymentPlanEffectivelyEmpty($paymentPlanData)) {
                unset($data['payment_plan']);
            } else {
                $installmentsData = $paymentPlanData['installments'] ?? null;
                unset($paymentPlanData['installments'], $paymentPlanData['id']);

                $downPayment = $paymentPlanData['down_payment'] ?? null;
                if (!$downPayment || (float) $downPayment <= 0) {
                    $paymentPlanData['down_payment_due_date'] = null;
                }

                $this->ensureCompletePaymentPlan($paymentPlanData);

                if ($event->paymentPlan) {
                    $plan = $event->paymentPlan;
                    $plan->update($paymentPlanData);
                    $plan->refresh();
                } else {
                    $plan = $event->paymentPlan()->create($paymentPlanData);
                }

                if ($installmentsData && is_array($installmentsData) && !empty($installmentsData)) {
                    $this->updateInstallments($event, $plan, $installmentsData);
                } else {
                    $event->receivables()
                        ->where('receivableable_type', get_class($event))
                        ->where('receivableable_id', $event->id)
                        ->delete();

                    $this->generateReceivables($event, $plan);
                }
            }
        }



        $companyIds = $data['company_ids'] ?? [];
        unset($data['company_ids']);

        $data['competing_company_id'] = $data['competing_company_id'] ?? null;
        $data['lost_reason_id'] = $data['lost_reason_id'] ?? null;
        $data['is_draft'] = false;

        if (isset($data['event_status_id'])) {
            $newStatus = EventStatus::find($data['event_status_id']);
            if ($event->event_status_id !== $data['event_status_id'] && $newStatus && $newStatus->name === 'Fechado') {
                $data['closed_at'] = Carbon::now();
            }
        }

        $event->update($data);

        if (isset($data['event_status_id']) && $event->event_status_id != $data['event_status_id']) {
            EventStatusHistory::create([
                'event_id' => $event->id,
                'event_status_id' => $event->event_status_id,
                'collaborator_id' => auth()->user()->collaborator->id
            ]);
        }

        $this->handleConsumptionReport($event, $consumptionReport, $consumptionReportFileName, $consumptionReportRemove);

        if ($request->has('company_ids')) {
            $event->companies()->sync($companyIds ?? []);
        }

        $event->refresh();
        $event->load('companies');

        $this->historyService->logEventUpdate($event, $originalDataWithRelationships, $data);

        return response()->json(new EventResource($event), 200);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json([], 204);
    }

    public function getFormData(?Event $event = null)
    {
        $eventCaptures = EventCapture::all();
        $eventTypes = EventType::all();
        $partners = Partner::all();
        $decorators = Decorator::all();
        $ceremonials = Ceremonial::all();
        $eventStatuses = EventStatus::all();
        $competingCompany = CompetingCompany::all();
        $lostReason = LostReason::all();

        $clients = [];
        $invoiceClients = [];
        $collaborators = [];
        $eventPlaces = [];
        $relatedTastingEvents = [];
        $parentEvents = [];
        $hasTasting = false;

        $eventCompanies = Company::whereIn('internal_name', ['Parthenon', 'Fastbar'])->whereNull('deleted_at')->get(['id', 'internal_name']);

        $companyIds = [];
        if ($event && $event->id) {
            $clients = Client::where('id', $event->client_id)->get();
            $invoiceClients = Client::where('id', $event->invoice_client_id)->get();
            $collaborators = Collaborator::where('id', $event->collaborator_id)->get();
            $eventPlaces = EventPlace::where('id', $event->event_place_id)->get();
            $hasTasting = $event->tastingEvents()->exists();
            $parentEvents = Event::whereIn('id', $event->parentEvents()->pluck('events.id'))
                ->get(['id', 'name']);
            $relatedTastingEvents = Event::whereIn('id', $event->tastingEvents()->pluck('events.id'))
                ->get(['id', 'name']);
            $companyIds = $event->companies()->pluck('companies.id')->toArray();
        }

        return response()->json([
            'event_companies' => $eventCompanies,
            'company_ids' => $companyIds,
            'event_captures' => $eventCaptures,
            'event_types' => $eventTypes,
            'partners' => $partners,
            'decorators' => $decorators,
            'ceremonials' => $ceremonials,
            'event_statuses' => $eventStatuses,
            'event_places' => $eventPlaces,
            'clients' => $clients,
            'invoice_clients' => $invoiceClients,
            'collaborators' => $collaborators,
            'competing_companies' => $competingCompany,
            'lost_reasons' => $lostReason,
            'related_tasting_events' => $relatedTastingEvents,
            'has_tasting' => $hasTasting,
            'parent_events' => $parentEvents
        ], 200);
    }



    public function gifts(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $perPage = $request->input('per_page', 10);

        $query = EventGift::query();

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventGifts = $query->where('event_id', $event->id)->paginate($perPage);

        return response()->json([
            'data' => EventGiftResource::collection($eventGifts),
            'pagination' => [
                'page' => $eventGifts->currentPage(),
                'total_pages' => $eventGifts->lastPage(),
                'per_page' => $eventGifts->perPage(),
                'total' => $eventGifts->total(),
            ],
            'soretable_fields' => Event::sortableFields()
        ], 200);
    }

    public function materials(Request $request, Event $event)
    {
        $this->authorize('view', $event);
        $materials = Material::all();

        $materials = $materials->map(function ($material) use ($event) {
            $materialEvent = $event->materials->where('id', $material->id)->first();
            $material->selected = $materialEvent ? ($materialEvent->pivot->selected === null ? '-1' : $materialEvent->pivot->selected) : null;
            $material->quantity = $materialEvent ? $materialEvent->pivot->quantity : 0;
            return $material;
        });

        return response()->json([
            'data' => $materials
        ], 200);
    }

    public function extraSales(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $perPage = $request->input('per_page', 10);

        $query = ExtraSale::query();

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $extraSales = $query->where('event_id', $event->id)->paginate($perPage);

        return response()->json([
            'data' => ExtraSaleResource::collection($extraSales),
            'pagination' => [
                'page' => $extraSales->currentPage(),
                'total_pages' => $extraSales->lastPage(),
                'per_page' => $extraSales->perPage(),
                'total' => $extraSales->total(),
            ],
        ], 200);
    }

    public function getOfficeEventsWithCollaboratorsForEvent($eventId)
    {
        $officeEvents = OfficeEvent::with(['collaborators' => function ($query) use ($eventId) {
            $query->where('event_office_collaborators.event_id', $eventId);
        }])
            ->get()
            ->each(function ($officeEvent) use ($eventId) {
                $officeEvent->collaborators = $officeEvent->collaborators->filter(function ($collaborator) use ($eventId) {
                    return $collaborator->pivot->event_id == $eventId;
                })->values();
            });

        return response()->json($officeEvents);
    }

    public function getOfficeBudgetsWithCollaboratorsForEvent($eventId)
    {
        $officeBudgets = OfficeBudget::with(['collaborators' => function ($query) use ($eventId) {
            $query->where('budget_office_collaborators.event_id', $eventId);
        }])
            ->get()
            ->each(function ($officeBudget) use ($eventId) {
                $officeBudget->collaborators = $officeBudget->collaborators->filter(function ($collaborator) use ($eventId) {
                    return $collaborator->pivot->event_id == $eventId;
                })->values();
            });

        return response()->json($officeBudgets);
    }


    public function updateExtraSales(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $extraSales = $request->input('extraSales', []);

        foreach ($extraSales as $extraSaleData) {
            $extraSale = ExtraSale::find($extraSaleData['id']);

            if ($extraSale && $extraSale->event_id == $event->id) {
                $extraSale->quantity = $extraSaleData['quantity'];
                $extraSale->unit_value = $extraSaleData['unit_value'];
                $extraSale->save();
            }
        }

        return response()->json(['message' => 'Vendas extras atualizadas com sucesso'], 200);
    }

    public function eventKits(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $perPage = $request->input('per_page', 10);

        $query = EventKit::query();

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventKit = $query->where('event_id', $event->id)->paginate($perPage);

        return response()->json([
            'data' => EventKitResource::collection($eventKit),
            'pagination' => [
                'page' => $eventKit->currentPage(),
                'total_pages' => $eventKit->lastPage(),
                'per_page' => $eventKit->perPage(),
                'total' => $eventKit->total(),
            ],
        ], 200);
    }

    public function contracts(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        $perPage = $request->input('per_page', 10);

        $query = Contract::query();

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $contract = $query->where('event_id', $contract->id)->paginate($perPage);

        return response()->json([
            'data' => ContractResource::collection($contract),
            'pagination' => [
                'page' => $contract->currentPage(),
                'total_pages' => $contract->lastPage(),
                'per_page' => $contract->perPage(),
                'total' => $contract->total(),
            ],
        ], 200);
    }

    public function resetContract(Event $event)
    {
        $this->authorize('resetContract', $event);

        $eventType = $event->eventType;
        $event->update([
            'contract_body' => $eventType->contract_text
        ]);
    }

    public function attachLegalRepresentatives(Request $request, Event $event)
    {
        $this->authorize('attachLegalRepresentatives', $event);

        $originalLegalRepresentatives = $event->clients()->where('type', 'pf')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
            ];
        })->toArray();

        $event->clients()->sync($request->legal_representatives);

        $event->refresh();
        $newLegalRepresentatives = $event->clients()->where('type', 'pf')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
            ];
        })->toArray();

        $originalData = ['legal_representatives' => $originalLegalRepresentatives];
        $requestData = ['legal_representatives' => $newLegalRepresentatives];

        $this->historyService->logEventUpdate($event, $originalData, $requestData);
    }

    public function attachLegalRepresentative(Request $request, Event $event)
    {
        $this->authorize('attachLegalRepresentatives', $event);

        $originalLegalRepresentatives = $event->clients()->where('type', 'pf')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
            ];
        })->toArray();

        $event->clients()->attach($request->legalRepresentativeId);

        $event->refresh();
        $newLegalRepresentatives = $event->clients()->where('type', 'pf')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
            ];
        })->toArray();

        $originalData = ['legal_representatives' => $originalLegalRepresentatives];
        $requestData = ['legal_representatives' => $newLegalRepresentatives];

        $this->historyService->logEventUpdate($event, $originalData, $requestData);

        return response()->json([], 201);
    }

    public function detachLegalRepresentative(Event $event, $legalRepresentativeId)
    {
        $this->authorize('attachLegalRepresentatives', $event);

        $originalLegalRepresentatives = $event->clients()->where('type', 'pf')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
            ];
        })->toArray();

        $event->clients()->detach($legalRepresentativeId);

        $event->refresh();
        $newLegalRepresentatives = $event->clients()->where('type', 'pf')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
            ];
        })->toArray();

        $originalData = ['legal_representatives' => $originalLegalRepresentatives];
        $requestData = ['legal_representatives' => $newLegalRepresentatives];

        $this->historyService->logEventUpdate($event, $originalData, $requestData);

        return response()->json([], 204);
    }

    public function syncMaterials(Event $event, Material $material, Request $request)
    {
        $this->authorize('syncMaterials', $event);
        $relationship = $event->materials()->wherePivot('material_id', $material->id);

        if ($relationship->exists()) {
            $relationship->updateExistingPivot($material->id, $request->all());
            return response()->json([], 200);
        }

        $event->materials()->attach($material, $request->all());
        return response()->json([], 201);
    }

    public function officeEvents(Event $event)
    {
        $this->authorize('view', $event);
        $officeEvents = OfficeEvent::all();

        $officeEvents = $officeEvents->map(function ($officeEvent) use ($event) {
            $eventOfficeEvent = $event->officeEvents->where('id', $officeEvent->id)->first();
            $officeEvent->quantity = $eventOfficeEvent ? $eventOfficeEvent->pivot->quantity : 0;
            return $officeEvent;
        });

        return response()->json([
            'data' => $officeEvents
        ], 200);
    }

    public function getEventFailures(Event $event)
    {
        $id = $event->id;
        $failures = Failure::all();
        $eventFailures = Event::find($id)->failures()->get();

        $failures = $failures->map(function ($failure) use ($eventFailures) {
            $eventFailure = $eventFailures->firstWhere('id', $failure->id);
            $failure->deduct = $eventFailure ? $eventFailure->pivot->deduct : false;
            return $failure;
        });

        return response()->json($failures);
    }


    public function attachFailure(Request $request, Event $event)
    {
        $event->failures()->attach($request->failure_id, ['deduct' => $request->deduct]);
        return response()->json(['message' => 'Failure attached to event successfully'], 201);
    }

    public function detachFailure(Event $event, Failure $failure)
    {
        $event->failures()->detach($failure->id);
        return response()->json(['message' => 'Failure detached from event successfully'], 200);
    }

    public function generateBudget(Event $event)
    {
        $event->load([
            'client',
            'eventPlace',
            'eventKits',
            'eventBatches'
        ]);

        $date = Carbon::now()->format('d/m/Y');
        $client = $event->client;
        $place = $event->eventPlace;
        $kits = $event->eventKits()->whereNull('event_kit_id')->get();

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;
        foreach ($event->eventBatches as $eventBatch) {
            $totalGuests += $eventBatch->guests;
        }

        $footerPath = resource_path('views/pdfs/footer.html');
        $footer = file_get_contents($footerPath);
        $headerPath = resource_path('views/pdfs/header.html');
        $header = file_get_contents($headerPath);
        $fileName = 'orcamento-' . $event->id;
        $viewContent = view("/pdfs/budget-pdf", compact(['event', 'date', 'client', 'place', 'kits', 'totalGuests']));

        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName, $footer, $header);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateEventChecklist(Event $event)
    {
        $event->load([
            'eventStatus',
            'client',
            'eventPlace',
            'eventKits',
            'eventBatches',
            'clients'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }
        $client = $event->client;
        $place = $event->eventPlace;

        $themedTables = $event->eventKits()->where('checklist', true)->where('themed_table', true)->get();

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;

        foreach ($event->eventBatches as $eventbatch) {
            $totalGuests += $eventbatch->guests;
        }

        $eventResponsibleNames = implode(', ', $event->clients->pluck('name')->toArray());

        $fileName = 'checklist-evento-' . $event->id;
        $viewContent = view("/pdfs/event-checklist-pdf", compact(['event', 'client', 'place', 'totalGuests', 'eventResponsibleNames', 'themedTables']));

        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateClosure(Event $event)
    {
        $event->load([
            'eventStatus',
            'eventPlace',
            'clients',
            'eventBatches',
            'extraSales'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }
        $place = $event->eventPlace;

        $responsibles = $event->clients->pluck('name')->implode(', ');

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;

        foreach ($event->eventBatches as $eventBatch) {
            $totalGuests += $eventBatch->guests;
        }

        $extraSaleTotal = 0;
        foreach ($event->extraSales as $extraSale) {
            $extraSaleTotal += $extraSale->quantity * $extraSale->unit_value;
        }

        $fileName = 'fechamento-' . $event->id;
        $viewContent = view("/pdfs/closure-pdf", compact(['event', 'place', 'responsibles', 'totalGuests', 'extraSaleTotal']));

        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateSummary(Event $event)
    {
        $event->load([
            'eventStatus',
            'clients',
            'eventKits',
            'eventBatches',
            'materials'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }
        $responsibles = $event->clients->pluck('name')->implode(', ');

        $kits = $event->eventKits()->whereNull('event_kit_id')->get();

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;
        foreach ($event->eventBatches as $eventBatch) {
            $totalGuests += $eventBatch->guests;
        }

        $selectedMaterials = $event->materials->filter(function ($material) {
            return $material->pivot->selected == true;
        });

        $fileName = 'resumo-' . $event->id;
        $viewContent = view("/pdfs/summary-pdf", compact(['event', 'totalGuests', 'selectedMaterials', 'responsibles', 'kits']));

        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateBuffetContract(Event $event)
    {
        $event->load([
            'eventStatus',
            'client',
            'eventPlace',
            'paymentPlan.paymentMethod'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }
        $client = $event->client;
        $place = $event->eventPlace;

        $fileName = 'contrato-buffet-' . $event->id;
        $viewContent = view("/pdfs/buffet-contract-pdf", compact(['event', 'place', 'client']));
        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateEventItemChecklist(Event $event)
    {
        $event->load([
            'eventStatus'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }
        $fileName = 'check-list-itens-evento-' . $event->id;
        $viewContent = view("/pdfs/event-item-checklist-pdf");
        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateLeaseContract(Event $event)
    {
        $event->load([
            'eventStatus',
            'client'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }

        $client = $event->client;

        $fileName = 'contrato-locacao-' . $event->id;
        $viewContent = view("/pdfs/lease-contract-pdf", compact(['event', 'client']));
        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateMenu(Event $event)
    {
        return $this->generateMenuPdf($event, false, 'menu-do-cliente-');
    }

    public function generateMenuWithObservations(Event $event)
    {
        return $this->generateMenuPdf($event, true, 'cardapio-');
    }

    private function generateMenuPdf(Event $event, bool $showObservations, string $fileNamePrefix = 'menu-do-cliente-'): \Illuminate\Http\JsonResponse
    {
        $event->load([
            'eventStatus',
            'client',
            'eventPlace',
            'eventKits',
            'eventBatches'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }

        $date = Carbon::now()->format('d/m/Y');
        $client = $event->client;
        $place = $event->eventPlace;
        $kits = $event->eventKits()->whereNull('event_kit_id')->get();

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;
        foreach ($event->eventBatches as $eventBatch) {
            $totalGuests += $eventBatch->guests;
        }

        $headerPath = resource_path('views/pdfs/header.html');
        $header = file_get_contents($headerPath);
        $fileName = $fileNamePrefix . $event->id;
        $viewContent = view("/pdfs/menu-pdf", compact(['event', 'date', 'client', 'place', 'kits', 'totalGuests', 'showObservations']));

        try {
            $pdfUrl = FileService::generatePdf(viewContent: $viewContent, fileName: $fileName, footerContent: $header, headerContent: $header);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    public function generateCollaborators(Event $event)
    {
        $event->load([
            'eventStatus',
            'client',
            'eventPlace'
        ]);

        if ($event->eventStatus && $event->eventStatus->name !== 'Fechado') {
            return response()->json(['error' => 'Não é permitido gerar este PDF para eventos não fechados.'], 400);
        }

        $date = Carbon::now()->format('d/m/Y');
        $client = $event->client;
        $place = $event->eventPlace;

        $fileName = 'collaborators-' . $event->id;
        $viewContent = view("/pdfs/collaborators-pdf", compact(['event', 'date', 'client', 'place']));
        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }


    public function clearRelatedEvents($eventId)
    {
        $event = Event::findOrFail($eventId);
        $event->tastingEvents()->detach();
        $event->parentEvents()->detach();
        return response()->json(['message' => 'Eventos relacionados removidos com sucesso.']);
    }

    public function checkIfHasScheduledEvents(Event $event, Request $request)
    {
        $eventId = $event->id;
        $eventPlaceId = $request->input('event_place_id');
        $eventDate = $request->input('date');
        $status_id = $request->input('status_id');

        if (empty($eventDate) || empty($eventPlaceId)) {
            return response()->json([
                'conflicting_events' => [],
            ]);
        }

        $blockedStatusIds = EventStatus::whereIn('name', ['Fechado', 'Pré-Reserva', 'Planejamento', 'Concluído'])
            ->pluck('id')
            ->toArray();

        if (!in_array($status_id, $blockedStatusIds)) {
            return response()->json([
                'conflicting_events' => [],
            ]);
        }

        $conflictingEvents = Event::where('event_place_id', $eventPlaceId)
            ->whereDate('date', $eventDate)
            ->where('id', '<>', $eventId)
            ->whereIn('event_status_id', $blockedStatusIds)
            ->get(['id', 'name', 'event_status_id']);

        $conflictingEvents = $conflictingEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->name,
                'status' => EventStatus::where('id', $event->event_status_id)->value('name'),
            ];
        });


        return response()->json([
            'conflicting_events' => $conflictingEvents,
        ]);
    }

    private function handleConsumptionReport(Event $event, ?string $fileData, ?string $originalName, bool $removeRequested = false): void
    {
        $shouldSave = false;

        if ($fileData) {
            if ($event->postEventConsumptionReportFile) {
                FileService::delete($event->postEventConsumptionReportFile);
                $event->post_event_consumption_report_file_id = null;
            }

            $file = FileService::saveBase64File('events/consumption-reports', $fileData, $originalName);
            $event->post_event_consumption_report_file_id = $file->id;
            $shouldSave = true;
        } elseif ($removeRequested && $event->postEventConsumptionReportFile) {
            FileService::delete($event->postEventConsumptionReportFile);
            $event->post_event_consumption_report_file_id = null;
            $shouldSave = true;
        }

        if ($shouldSave) {
            $event->save();
            $event->load('postEventConsumptionReportFile');
        }
    }

    private function generateReceivables(Event $event, PaymentPlan $plan)
    {
        $today = now();
        $currentYear = $today->year;
        $currentMonth = $today->month;

        if ($plan->down_payment > 0) {
            $downPaymentDueDate = $plan->down_payment_due_date
                ? $plan->down_payment_due_date->format('Y-m-d')
                : $today->format('Y-m-d');

            $event->receivables()->create([
                'description' => 'Entrada - ' . $event->name,
                'amount' => $plan->down_payment,
                'due_date' => $downPaymentDueDate,
                'status' => 'pending',
                'payment_method_id' => $plan->payment_method_id,
                'receivableable_type' => get_class($event),
                'receivableable_id' => $event->id,
            ]);
        }

        $plan->installments()->delete();

        for ($i = 1; $i <= $plan->num_installments; $i++) {
            $dueMonth = $currentMonth + $i;
            $dueYear = $currentYear;

            if ($dueMonth > 12) {
                $dueMonth -= 12;
                $dueYear += 1;
            }

            $dueDate = \Carbon\Carbon::create($dueYear, $dueMonth, $plan->due_day);

            $plan->installments()->create([
                'installment_number' => $i,
                'amount' => $plan->installment_amount,
                'due_date' => $dueDate->format('Y-m-d'),
            ]);

            $event->receivables()->create([
                'description' => 'Parcela ' . $i . '/' . $plan->num_installments . ' - ' . $event->name,
                'amount' => $plan->installment_amount,
                'due_date' => $dueDate->format('Y-m-d'),
                'status' => 'pending',
                'payment_method_id' => $plan->payment_method_id,
                'receivableable_type' => get_class($event),
                'receivableable_id' => $event->id,
            ]);
        }
    }

    public function duplicate(Event $event)
    {
        DB::beginTransaction();

        try {
            $event->load([
                'materials',
                'eventBatches',
                'extraSales',
                'eventNotes',
                'paymentPlan',
                'tastingEvents',
                'parentEvents',
                'officeEvents',
                'utensils',
                'officeBudgets',
                'clients',
                'companies',
                'eventKits'
            ]);

            $newEventData = $event->toArray();
            unset($newEventData['id'], $newEventData['created_at'], $newEventData['updated_at']);

            $newEventData['name'] = 'Cópia de ' . $event->name;
            $newEventData['event_status_id'] = EventStatus::where('name', 'Aberto')->value('id');
            $newEventData['collaborator_id'] = auth()->user()->collaborator->id;

            $newEvent = Event::create($newEventData);

            if ($event->eventBatches->isNotEmpty()) {
                foreach ($event->eventBatches as $batch) {
                    EventBatch::create([
                        'event_id' => $newEvent->id,
                        'name' => $batch->name,
                        'guests' => $batch->guests,
                        'value' => $batch->value,
                        'discount' => $batch->discount,
                        'total_value' => $batch->total_value,
                        'status' => $batch->status,
                        'unit_value' => $batch->unit_value,
                    ]);
                }
            }

            if ($event->materials->isNotEmpty()) {
                $materialsData = [];
                foreach ($event->materials as $material) {
                    $materialsData[$material->id] = [
                        'selected' => $material->pivot->selected === '-1' ? null : $material->pivot->selected,
                        'quantity' => $material->pivot->quantity ?? 0
                    ];
                }
                $newEvent->materials()->sync($materialsData);
            }

            if ($event->eventNotes->isNotEmpty()) {
                foreach ($event->eventNotes as $note) {
                    $noteData = [
                        'event_id' => $newEvent->id,
                        'text' => $note->text,
                        'user_id' => auth()->user()->collaborator->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];


                    EventNote::create($noteData);
                }
            }

            if ($event->extraSales->isNotEmpty()) {
                foreach ($event->extraSales as $sale) {
                    ExtraSale::create([
                        'event_id' => $newEvent->id,
                        'description' => $sale->description,
                        'unit_value' => $sale->unit_value,
                        'quantity' => $sale->quantity,
                        'unit_id' => $sale->unit_id
                    ]);
                }
            }

            if ($event->paymentPlan) {
                $paymentPlanData = $event->paymentPlan->toArray();
                unset($paymentPlanData['id']);

                $downPayment = $paymentPlanData['down_payment'] ?? null;
                if (!$downPayment || (float) $downPayment <= 0) {
                    $paymentPlanData['down_payment_due_date'] = null;
                }

                $newPaymentPlan = $newEvent->paymentPlan()->create($paymentPlanData);
                $this->generateReceivables($newEvent, $newPaymentPlan);
            }

            if ($event->utensils->isNotEmpty()) {
                $newEvent->utensils()->sync($event->utensils->pluck('id'));
            }

            if ($event->officeEvents->isNotEmpty()) {
                $officeData = [];
                foreach ($event->officeEvents as $office) {
                    $officeData[$office->id] = ['quantity' => $office->pivot->quantity ?? 0];
                }
                $newEvent->officeEvents()->sync($officeData);
            }

            if ($event->officeBudgets->isNotEmpty()) {
                $newEvent->officeBudgets()->sync($event->officeBudgets->pluck('id'));
            }

            if ($event->tastingEvents->isNotEmpty()) {
                $newEvent->tastingEvents()->sync($event->tastingEvents->pluck('id'));
            }

            if ($event->parentEvents->isNotEmpty()) {
                $newEvent->parentEvents()->sync($event->parentEvents->pluck('id'));
            }

            if ($event->clients->isNotEmpty()) {
                $legalRepresentativesIds = $event->clients
                    ->where('type', 'pf')
                    ->pluck('id')
                    ->toArray();

                if (!empty($legalRepresentativesIds)) {
                    $newEvent->clients()->syncWithoutDetaching($legalRepresentativesIds);
                }
            }

            if ($event->companies->isNotEmpty()) {
                $newEvent->companies()->sync($event->companies->pluck('id')->toArray());
            }

            if (EventKit::where('event_id', $event->id)->exists()) {
                $this->duplicateEventKits($event, $newEvent);
            }

            EventStatusHistory::create([
                'event_id' => $newEvent->id,
                'event_status_id' => $newEvent->event_status_id,
                'collaborator_id' => auth()->user()->collaborator->id
            ]);

            DB::commit();

            return response()->json(new EventResource($newEvent->load([
                'materials',
                'eventBatches',
                'extraSales',
                'eventNotes',
                'paymentPlan',
                'tastingEvents',
                'parentEvents'
            ])), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao duplicar evento',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    private function duplicateEventKits(Event $originalEvent, Event $newEvent)
    {
        $rootKits = EventKit::where('event_id', $originalEvent->id)
            ->whereNull('event_kit_id')
            ->get();

        $kitIdMap = [];

        foreach ($rootKits as $rootKit) {
            $rootKit->load(['products', 'eventKits.products']);

            $newRootKit = $this->duplicateSingleKit($rootKit, $newEvent->id, null, $kitIdMap);
            $kitIdMap[$rootKit->id] = $newRootKit->id;
        }
    }

    private function duplicateSingleKit(EventKit $originalKit, $newEventId, $newParentKitId, &$kitIdMap)
    {
        $newKit = EventKit::create([
            'name' => $originalKit->name,
            'event_id' => $newEventId,
            'event_kit_id' => $newParentKitId,
            'value' => $originalKit->value,
            'themed_table' => $originalKit->themed_table,
            'ordination' => $originalKit->ordination,
            'observation' => $originalKit->observation,
            'optional' => $originalKit->optional,
            'checklist' => $originalKit->checklist,
        ]);

        $originalKit->load('products');

        $productsData = [];
        foreach ($originalKit->products as $product) {
            $productsData[$product->id] = [
                'value' => $product->pivot->value,
                'ordination' => $product->pivot->ordination,
                'optional' => $product->pivot->optional,
                'average consumption' => $product->pivot->{'average consumption'},
                'quantity' => $product->pivot->quantity,
                'comments' => $product->pivot->comments,
                'checklist' => $product->pivot->checklist,
                'observation' => $product->pivot->observation,
            ];
        }

        if (!empty($productsData)) {
            $newKit->products()->sync($productsData);
        }

        $originalKit->load('eventKits');

        foreach ($originalKit->eventKits as $subKit) {
            $newSubKit = $this->duplicateSingleKit($subKit, $newEventId, $newKit->id, $kitIdMap);
            $kitIdMap[$subKit->id] = $newSubKit->id;
        }

        return $newKit;
    }

    private function updateInstallments(Event $event, $plan, array $installmentsData): void
    {
        if (!$plan) {
            return;
        }

        if (empty($installmentsData)) {
            $plan->installments()->delete();
            return;
        }

        $this->validateInstallmentTotals($plan, $installmentsData);
        $this->updatePlanInstallmentAmount($plan, $installmentsData);

        $existingInstallmentsData = array_filter($installmentsData, fn($data) => isset($data['id']) && $data['id'] !== null);
        $newInstallmentsData = array_filter($installmentsData, fn($data) => !isset($data['id']) || $data['id'] === null);

        $installmentIds = collect($existingInstallmentsData)->pluck('id')->filter()->toArray();

        $allExistingInstallments = Installment::where('payment_plan_id', $plan->id)
            ->orderBy('installment_number')
            ->get()
            ->keyBy('id');

        $installmentsToUpdate = [];
        if (!empty($installmentIds)) {
            $installmentsToUpdate = Installment::where('payment_plan_id', $plan->id)
                ->whereIn('id', $installmentIds)
                ->orderBy('installment_number')
                ->get()
                ->keyBy('id');
        }

        $installmentsToDelete = $allExistingInstallments->whereNotIn('id', $installmentIds);
        foreach ($installmentsToDelete as $installmentToDelete) {
            $event->receivables()
                ->where('receivableable_type', get_class($event))
                ->where('receivableable_id', $event->id)
                ->where('description', 'like', '%Parcela ' . $installmentToDelete->installment_number . '/' . $plan->num_installments . '%')
                ->delete();

            $installmentToDelete->delete();
        }

        $previousDueDate = null;

        foreach ($existingInstallmentsData as $installmentData) {
            if (!isset($installmentData['id']) || !isset($installmentData['due_date'])) {
                continue;
            }

            $installment = $installmentsToUpdate->get($installmentData['id']) ?? null;

            if (!$installment) {
                $installment = Installment::where('id', $installmentData['id'])
                    ->where('payment_plan_id', $plan->id)
                    ->first();

                if (!$installment) {
                    continue;
                }
            }

            if ($installment->payment_plan_id != $plan->id) {
                continue;
            }

            $newDueDate = \Carbon\Carbon::parse($installmentData['due_date']);

            if ($previousDueDate && $newDueDate->lt($previousDueDate)) {
                $validator = Validator::make([], []);
                $validator->errors()->add('installments', 'A data de vencimento da parcela ' . $installment->installment_number . ' não pode ser anterior à parcela anterior.');
                throw new ValidationException($validator);
            }

            $installment->due_date = $newDueDate->format('Y-m-d');
            $amount = $this->resolveInstallmentAmount($plan, $installmentData);
            $installment->amount = $amount;
            $installment->save();

            $receivable = $event->receivables()
                ->where('receivableable_type', get_class($event))
                ->where('receivableable_id', $event->id)
                ->where('description', 'like', '%Parcela ' . $installment->installment_number . '/' . $plan->num_installments . '%')
                ->first();

            if ($receivable) {
                $receivable->due_date = $newDueDate->format('Y-m-d');
                $receivable->amount = $amount;
                $receivable->save();
            }

            $previousDueDate = $newDueDate;
        }

        $maxInstallmentNumber = Installment::where('payment_plan_id', $plan->id)
            ->max('installment_number') ?? 0;

        foreach ($newInstallmentsData as $installmentData) {
            if (!isset($installmentData['due_date'])) {
                continue;
            }

            $newDueDate = Carbon::parse($installmentData['due_date']);

            if ($previousDueDate && $newDueDate->lt($previousDueDate)) {
                $validator = \Illuminate\Support\Facades\Validator::make([], []);
                $validator->errors()->add('installments', 'A data de vencimento da nova parcela não pode ser anterior à parcela anterior.');
                throw new ValidationException($validator);
            }

            if (isset($installmentData['installment_number'])) {
                $installmentNumber = $installmentData['installment_number'];
            } else {
                $maxInstallmentNumber++;
                $installmentNumber = $maxInstallmentNumber;
            }

            $existingInstallment = Installment::withTrashed()
                ->where('payment_plan_id', $plan->id)
                ->where('installment_number', $installmentNumber)
                ->first();

            if ($existingInstallment) {
                if ($existingInstallment->trashed()) {
                    $existingInstallment->restore();
                }

                $existingInstallment->due_date = $newDueDate->format('Y-m-d');
                $amount = $this->resolveInstallmentAmount($plan, $installmentData);
                $existingInstallment->amount = $amount;
                $existingInstallment->save();

                $receivable = $event->receivables()
                    ->where('receivableable_type', get_class($event))
                    ->where('receivableable_id', $event->id)
                    ->where('description', 'like', '%Parcela ' . $installmentNumber . '/' . $plan->num_installments . '%')
                    ->first();

                if ($receivable) {
                    $receivable->due_date = $newDueDate->format('Y-m-d');
                    $receivable->amount = $amount;
                    $receivable->save();
                } else {
                    $event->receivables()->create([
                        'description' => 'Parcela ' . $installmentNumber . '/' . $plan->num_installments . ' - ' . $event->name,
                        'amount' => $amount,
                        'due_date' => $newDueDate->format('Y-m-d'),
                        'status' => 'pending',
                        'payment_method_id' => $plan->payment_method_id,
                        'receivableable_type' => get_class($event),
                        'receivableable_id' => $event->id,
                    ]);
                }
            } else {
                $newInstallment = Installment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => $installmentNumber,
                    'amount' => $this->resolveInstallmentAmount($plan, $installmentData),
                    'due_date' => $newDueDate->format('Y-m-d'),
                ]);

                $event->receivables()->create([
                    'description' => 'Parcela ' . $installmentNumber . '/' . $plan->num_installments . ' - ' . $event->name,
                    'amount' => $this->resolveInstallmentAmount($plan, $installmentData),
                    'due_date' => $newDueDate->format('Y-m-d'),
                    'status' => 'pending',
                    'payment_method_id' => $plan->payment_method_id,
                    'receivableable_type' => get_class($event),
                    'receivableable_id' => $event->id,
                ]);
            }

            $previousDueDate = $newDueDate;
        }
    }

    /**
     * Retorna o histórico de alterações de um evento
     */
    public function history(Request $request, Event $event)
    {
        $perPage = $request->input('per_page', 15);
        $histories = $this->historyService->getEventHistory($event->id, $perPage);

        return response()->json([
            'data' => EventHistoryResource::collection($histories),
            'pagination' => [
                'page' => $histories->currentPage(),
                'total_pages' => $histories->lastPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
            ],
        ], 200);
    }

    private function resolveInstallmentAmount(PaymentPlan $plan, array $installmentData): float
    {
        $amount = $installmentData['amount'] ?? $plan->installment_amount ?? 0;
        return round((float) $amount, 2);
    }

    private function validateInstallmentTotals(PaymentPlan $plan, array $installmentsData): void
    {
        if ($plan->total_amount === null) {
            return;
        }

        $installmentSum = collect($installmentsData)
            ->map(fn($data) => $this->resolveInstallmentAmount($plan, $data))
            ->sum();

        $expectedTotal = round((float) $plan->total_amount, 2);
        $downPayment = round((float) ($plan->down_payment ?? 0), 2);
        $calculatedTotal = round($installmentSum + $downPayment, 2);

        if (abs($calculatedTotal - $expectedTotal) > 0.01) {
            $difference = round($expectedTotal - $calculatedTotal, 2);
            $differenceText = $difference > 0
                ? "faltam R$ " . number_format(abs($difference), 2, ',', '.')
                : "há excesso de R$ " . number_format(abs($difference), 2, ',', '.');

            $validator = Validator::make([], []);
            $validator->errors()->add(
                'payment_plan.total_amount',
                "A soma das parcelas (considerando a entrada) deve ser igual ao valor total. Atualmente {$differenceText}"
            );

            throw new ValidationException($validator);
        }
    }

    private function updatePlanInstallmentAmount(PaymentPlan $plan, array $installmentsData): void
    {
        $count = count($installmentsData);

        if ($count === 0 || $plan->num_installments === 0) {
            return;
        }

        $installmentSum = collect($installmentsData)
            ->map(fn($data) => $this->resolveInstallmentAmount($plan, $data))
            ->sum();

        if ($count === (int) $plan->num_installments) {
            $plan->installment_amount = round($installmentSum / $plan->num_installments, 2);
            $plan->save();
        }
    }

    private function ensureCompletePaymentPlan(array $paymentPlanData): void
    {
        $requiredFields = ['num_installments', 'payment_method_id', 'due_day'];

        $hasRequired = collect($requiredFields)->every(function ($field) use ($paymentPlanData) {
            $value = $paymentPlanData[$field] ?? null;
            return $value !== null && $value !== '' && $value !== 0 && $value !== '0';
        });

        if (!$hasRequired) {
            $validator = Validator::make([], []);
            $validator->errors()->add(
                'payment_plan.total_amount',
                'Preencha todos os campos de pagamento ou deixe todos vazios.'
            );
            throw new ValidationException($validator);
        }

        $downPayment = $paymentPlanData['down_payment'] ?? null;
        $downPaymentDueDate = $paymentPlanData['down_payment_due_date'] ?? null;

        if ($downPayment && (float) $downPayment > 0) {
            if (!$downPaymentDueDate || trim($downPaymentDueDate) === '') {
                $validator = Validator::make([], []);
                $validator->errors()->add(
                    'payment_plan.down_payment_due_date',
                    'A data de vencimento da entrada é obrigatória quando há valor de entrada.'
                );
                throw new ValidationException($validator);
            }
        }
    }

    private function isPaymentPlanEffectivelyEmpty(array $paymentPlanData): bool
    {
        $meaningfulFields = ['num_installments', 'payment_method_id', 'due_day', 'down_payment', 'installment_amount'];

        $hasMeaningful = collect($meaningfulFields)->contains(function ($field) use ($paymentPlanData) {
            $value = $paymentPlanData[$field] ?? null;
            return !($value === null || $value === '' || $value === 0 || $value === '0');
        });

        if ($hasMeaningful) {
            return false;
        }

        return true;
    }
}
