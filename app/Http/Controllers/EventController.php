<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\ContractResource;
use App\Http\Resources\EventGiftResource;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\ExtraSaleResource;
use App\Http\Resources\EventKitResource;
use App\Models\Event;
use App\Models\EventGift;
use App\Models\Material;
use App\Models\ExtraSale;
use App\Models\EventKit;
use App\Models\Contract;
use App\Models\EventType;
use Illuminate\Http\Request;
use App\Http\Resources\EventCalendarResource;
use App\Models\Ceremonial;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\CompetingCompany;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use mikehaertl\wkhtmlto\Pdf;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventController::class, 'event');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Event::where('is_draft', false);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $operator = $field == 'name' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $value,
                        operator: $operator,
                        isDate: $field == 'date_time'
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sortField = $request->input('order_by');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

            if (str_contains($sortField, '.')) {
                [$relation, $field] = explode('.', $sortField);
                $query->whereHas($relation)->with($relation)->orderByRelation($relation, $field, $direction);
            } else {
                $query->orderBy($sortField, $direction);
            }
        }

        $events = $query->paginate($perPage);

        return response()->json([
            'data' => EventResource::collection($events),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'total_pages' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
            'sortable_fields' => Event::sortableFields(),
            'filterable_fields' => Event::filterableFields()
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

        $query->whereDate('date_time', '>=', $start)->whereDate('date_time', '<', $end);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $events = $query->get();

        return response()->json([
            'data' => EventCalendarResource::collection($events),
            'sortable_fields' => Event::sortableFields(),
            'filterable_fields' => Event::filterableFields()
        ], 200);
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->all();
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

        if (!empty($data['legal_representatives'])) {
            $event->clients()->sync($data['legal_representatives']);
        }

        EventStatusHistory::create(['event_id' => $event->id, 'event_status_id' => $event->event_status_id, 'collaborator_id' => auth()->user()->collaborator->id]);

        return response()->json(new EventResource($event), 201);
    }

    public function show(Event $event)
    {
        return response()->json(new EventResource($event), 200);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->all();

        Log::info("message", ['data' => $data]);

        if (!empty($data['event_batches'])) {
            $batchesIds = collect($data['event_batches']);
            $batchesIds = $batchesIds->where('id', '!=', null)->pluck('id')->toArray();
            $batchesToDelete = $event->eventBatches->whereNotIn('id', $batchesIds);
            foreach ($batchesToDelete as $batch) {
                $batch->delete();
            }
            foreach ($data['event_batches'] as $batch) {
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
            $officeArray = collect($data['office_events'])->mapWithKeys(function ($office) {
                return [
                    $office['id'] => [
                        'quantity' => !empty($office['quantity']) ? $office['quantity'] : 0
                    ]
                ];
            })->toArray();
            $event->officeEvents()->sync($officeArray);
        }

        if (!empty($data['clients'])) {
            $event->clients()->sync($data['clients']);
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

        if (!empty($data['related_events']) || !empty($data['events_with_tastings'])) {
            if (!empty($data['related_events'])) {
                $relatedTastingEvents = $data['related_events'];
            
                $event->tastings()->sync($relatedTastingEvents);
            
                foreach ($relatedTastingEvents as $tastingEventId) {
                    $tastingEvent = Event::find($tastingEventId);
                    $tastingEvent->eventsWithTastings()->syncWithoutDetaching([$event->id]);
                }
            }
            if (!empty($data['events_with_tastings'])) {
                $eventsWithTastings = $data['events_with_tastings'];
                
                $event->eventsWithTastings()->sync($eventsWithTastings);
            
                foreach ($eventsWithTastings as $mainEventId) {
                    $mainEvent = Event::find($mainEventId);
                    
                    $mainEvent->tastings()->syncWithoutDetaching([$event->id]);
                }
            }
        }
        

        if (!empty($data['legal_representatives'])) {
            $event->clients()->sync($data['legal_representatives']);
        }
        
        $data['competing_company_id'] = $data['competing_company_id'] ?? null;
        $data['lost_reason_id'] = $data['lost_reason_id'] ?? null;
        $data['is_draft'] = false;

        $event->update($data);

        if ($event->event_status_id != $data['event_status_id']) {
            EventStatusHistory::create(['event_id' => $event->id, 'event_status_id' => $event->event_status_id, 'collaborator_id' => auth()->user()->collaborator->id]);
        }

        return response()->json(new EventResource($event), 200);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json([], 204);
    }

    public function getFormData(Event $event)
    {
        $eventCaptures = EventCapture::all();
        $eventTypes = EventType::all();
        $partners = Partner::all();
        $decorators = Decorator::all();
        $ceremonials = Ceremonial::all();
        $eventStatuses = EventStatus::all();
        $eventPlaces = EventPlace::with(['address.city', 'address.state'])->get();
        $competingCompany = CompetingCompany::all();
        $lostReason = LostReason::all();
        $events = Event::all();


        $clients = [];
        $invoiceClients = [];
        $collaborators = [];
        $relatedEvents = [];

        if ($event) {
            $clients = Client::where('id', $event->client_id)->get();
            $invoiceClients = Client::where('id', $event->invoice_client_id)->get();
            $collaborators = Collaborator::where('id', $event->collaborator_id)->get();
            $relatedEvents = $event->tastings()->pluck('events.id');
            $hasTasting = $event->tastings()->exists();
            $eventsWithTastings = $event->eventsWithTastings()->pluck('events.id');
        }

        return response()->json([
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
            'events' => $events,
            'competing_companies' => $competingCompany,
            'lost_reasons' => $lostReason,
            'related_events' => $relatedEvents,
            'has_tasting' => $hasTasting,
            'events_with_tastings' => $eventsWithTastings
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
                'current_page' => $eventGifts->currentPage(),
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
                'current_page' => $extraSales->currentPage(),
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
                'current_page' => $eventKit->currentPage(),
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
                'current_page' => $contract->currentPage(),
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
        $event->clients()->sync($request->legal_representatives);
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
        $pdf = new Pdf();    

        $date = Carbon::now()->format('d/m/Y');
        $client = $event->client;
        $place = $event->eventPlace;
        $kits = $event->eventKits()->whereNull('event_kit_id')->get();

        $html = view("/pdfs/budget-pdf", compact(['event', 'date', 'client', 'place', 'kits']))->render();
        $pdf->addPage($html);


        $fileName = 'orcamento-' . $event->id . '.pdf';
        $directoryPath = storage_path('app/public/pdfs');

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        $filePath = "{$directoryPath}/{$fileName}";

        if (!$pdf->saveAs($filePath)) {
            return response()->json(['error' => $pdf->getError()], 500);
        }

        $pdfUrl = asset("storage/pdfs/{$fileName}");
        return response()->json(['pdf_url' => $pdfUrl]);
    }

    public function generateEventChecklist(Event $event)
    {
        $pdf = new Pdf();    

        $client = $event->client;
        $place = $event->eventPlace;

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;

        foreach($event->eventBatches as $eventbatch) {
            $totalGuests += $eventbatch->guests;
        }

        $eventResponsibleNames = implode(', ', $event->clients->pluck('name')->toArray());

        $html = view("/pdfs/event-checklist-pdf", compact(['event', 'client', 'place', 'totalGuests', 'eventResponsibleNames']))->render();
        $pdf->addPage($html);


        $fileName = 'checklist-evento-' . $event->id . '.pdf';
        $directoryPath = storage_path('app/public/pdfs');

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        $filePath = "{$directoryPath}/{$fileName}";

        if (!$pdf->saveAs($filePath)) {
            return response()->json(['error' => $pdf->getError()], 500);
        }

        $pdfUrl = asset("storage/pdfs/{$fileName}");
        return response()->json(['pdf_url' => $pdfUrl]);
    }

    public function generateClosure(Event $event)
    {
        $pdf = new Pdf();    

        $place = $event->eventPlace;

        $responsibles = $event->clients->pluck('name')->implode(', ');

        $totalGuests = $event->event_guests + $event->parthenon_courtesies + $event->partner_courtesies;

        foreach($event->eventBatches as $eventBatch) { 
            $totalGuests += $eventBatch->guests;
        }
        
        $extraSaleTotal = 0;
        foreach($event->extraSales as $extraSale) {
            $extraSaleTotal += $extraSale->quantity * $extraSale->unit_value;
        }


        $html = view("/pdfs/closure-pdf", compact(['event', 'place', 'responsibles', 'totalGuests', 'extraSaleTotal']))->render();
        $pdf->addPage($html);


        $fileName = 'fechamento-' . $event->id . '.pdf';
        $directoryPath = storage_path('app/public/pdfs');

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        $filePath = "{$directoryPath}/{$fileName}";

        if (!$pdf->saveAs($filePath)) {
            return response()->json(['error' => $pdf->getError()], 500);
        }

        $pdfUrl = asset("storage/pdfs/{$fileName}");
        return response()->json(['pdf_url' => $pdfUrl]);
    }

    public function generatePdf(Request $request)
    {
        $item = $request->input('item');
        $pdf = new Pdf();


        $slug = Str::slug($item, '-');

        switch ($item) {
            case 'Resumo':
                $html = view("/pdfs/summary-pdf")->render();
                break;
            case 'Orçamento':
                $html = view("/pdfs/budget-pdf", compact(['test' => 'dfg']))->render();
                break;
            case 'Orçamento Timbrado':
                $html = view("/pdfs/letterhead-budget-pdf")->render();
                break;
            case 'Contrato de Buffet':
                $html = view("/pdfs/buffet-contract-pdf")->render();
                break;
            case 'Contrato de Locação':
                $html = view("/pdfs/lease-contract-pdf")->render();
                break;
            case 'Cardápio':
                $html = view("/pdfs/menu-pdf")->render();
                break;
            case 'Colaboradores':
                $html = view("/pdfs/collaborators-pdf")->render();
                break;
            case 'Fechamento':
                $html = view("/pdfs/closure-pdf")->render();
                break;
            case 'Check List Evento':
                $html = view("/pdfs/event-checklist-pdf")->render();
                break;
            case 'Check List de Itens do Evento':
                $html = view("/pdfs/event-items-checklist-pdf")->render();
                break;
            default:
                return response()->json(['error' => 'View não encontrada para o item especificado'], 400);
        }


        $pdf->addPage($html);

        $fileName = $slug . '.pdf';
        $directoryPath = storage_path('app/public/pdfs');

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        $filePath = "{$directoryPath}/{$fileName}";

        if (!$pdf->saveAs($filePath)) {
            return response()->json(['error' => $pdf->getError()], 500);
        }

        $pdfUrl = asset("storage/pdfs/{$fileName}");
        return response()->json(['pdf_url' => $pdfUrl]);
    }
    

public function clearRelatedEvents($eventId)
{
    $event = Event::findOrFail($eventId);
    $event->tastings()->detach();
    $event->eventsWithTastings()->detach();
    return response()->json(['message' => 'Eventos relacionados removidos com sucesso.']);
}


}
