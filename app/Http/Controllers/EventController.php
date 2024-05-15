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
use App\Models\EventStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventController::class, 'event');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Event::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $operator = $field == 'name' ? 'like' : '=';
                $query->filterByField(
                    field: $field, 
                    value: $value,
                    operator: $operator,
                    isDate: $field == 'date_time'
                );
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
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

        Event::create($request->all());

        if (isset($data['materials'])) {
            $event->materials()->sync($data['materials']);
        }

        if (isset($data['utensils'])) {
            $event->utensils()->sync($data['utensils']);
        }

        if (isset($data['office_events'])) {
            $event->officeEvents()->sync($data['office_events']);
        }

        if (isset($data['office_budgets'])) {
            $event->officeBudgets()->sync($data['office_budgets']);
        }

        if (isset($data['legal_representatives'])) {
            $event->clients()->sync($data['legal_representatives']);
        }

        if (isset($data['event_kits'])) {
            foreach ($data['event_kits'] as $eventKit) {
                $eventKit['event_id'] = $event->id;
                EventKit::create($eventKit);
            }
        }

        if (isset($data['event_gifts'])) {
            foreach ($data['event_gifts'] as $eventGift) {
                $eventGift['event_id'] = $event->id;
                EventGift::create($eventGift);
            }
        }

        return response()->json(new EventResource($event), 201);
    }

    public function show(Event $event)
    {
        return response()->json(new EventResource($event), 200);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->all();

        if (isset($data['materials'])) {
            $event->materials()->sync($data['materials']);
        }

        if (isset($data['office_events'])) {
            $event->officeEvents()->sync($data['office_events']);
        }

        if (isset($data['clients'])) {
            $event->clients()->sync($data['clients']);
        }

        if (isset($data['legal_representatives'])) {
            $event->clients()->sync($data['legal_representatives']);
        }

        if (isset($data['event_kits'])) {
            foreach ($data['event_kits'] as $eventKit) {
                $eventKit['event_id'] = $event->id;
                if (!empty($eventKit['id'])) {
                    EventKit::find($eventKit['id'])->update($eventKit);
                    continue;
                }
                EventKit::create($eventKit);
            }
        }

        $event->update($request->all());
        return response()->json(new EventResource($event), 200);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json([], 204);
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

        $perPage = $request->input('per_page', 10);
        $materialIds = $event->materials->pluck('id');

        $query = Material::query();

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $materials = $query->where('id', $materialIds[0])->paginate($perPage);

        return response()->json([
            'data' => MaterialResource::collection($materials),
            'pagination' => [
                'current_page' => $materials->currentPage(),
                'total_pages' => $materials->lastPage(),
                'per_page' => $materials->perPage(),
                'total' => $materials->total(),
            ],
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
        Log::info($request->all());
        $relationship = $event->materials()->wherePivot('material_id', $material->id);

        if ($relationship->exists()) {
            $relationship->updateExistingPivot($material->id, $request->all());
            return response()->json([], 200);
        }
        
        $event->materials()->attach($material, $request->all());
        return response()->json([], 201);
    }

    public function getEventKits(Event $event)
    {
        $query = EventKit::query();
        $query->where('event_id', $event->id)->whereNull('event_kit_id')->get();
        return response()->json(EventKitResource::collection($event->eventKits), 200);
    }
}
