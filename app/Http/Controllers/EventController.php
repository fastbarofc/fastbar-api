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
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
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
            foreach ($filterableArray as $field => $value) {
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
        $eventType = EventType::find($data['event_type_id']);
        $event = Event::create([
            'name'               => !empty($data['name']) ? $data['name'] : null,
            'slug'               => !empty($data['slug']) ? $data['slug'] : null,
            'event_type_id'      => !empty($data['event_type_id']) ? $data['event_type_id'] : null,
            'event_place_id'     => !empty($data['event_place_id']) ? $data['event_place_id'] : null,
            'event_status_id'    => !empty($data['event_status_id']) ? $data['event_status_id'] : null,
            'lease'              => !empty($data['lease']) ? $data['lease'] : null,
            'guests'             => !empty($data['guests']) ? $data['guests'] : null,
            'min_guests'         => !empty($data['min_guests']) ? $data['min_guests'] : null,
            'date_time'          => !empty($data['date_time']) ? $data['date_time'] : null,
            'unit_value'         => !empty($data['unit_value']) ? $data['unit_value'] : null,
            'discount'       => !empty($data['discount']) ? $data['discount'] : null,
            'unit_discount'       => !empty($data['unit_discount']) ? $data['unit_discount'] : null,
            'contract_body'      => !empty($data['contract_body']) ? $data['contract_body'] : $eventType->contract_text,
            'parthenon_courtesies' => !empty($data['parthenon_courtesies']) ? $data['parthenon_courtesies'] : null,
            'partner_courtesies'   => !empty($data['partner_courtesies']) ? $data['partner_courtesies'] : null,
            'courtesies'         => !empty($data['courtesies']) ? $data['courtesies'] : null,
        ]);

        Event::create($request->all());

        if (!empty($data['materials'])) {
            $event->materials()->sync($data['materials']);
        }

        if (!empty($data['utensils'])) {
            $event->utensils()->sync($data['utensils']);
        }

        if (!empty($data['office_events'])) {
            $event->officeEvents()->sync($data['office_events']);
        }

        if (!empty($data['office_budgets'])) {
            $event->officeBudgets()->sync($data['office_budgets']);
        }

        if (!empty($data['clients'])) {
            $event->clients()->sync($data['clients']);
        }

        if (!empty($data['event_kits'])) {
            foreach ($data['event_kits'] as $eventKit) {
                $eventKit['event_id'] = $event->id;
                EventKit::create($eventKit);
            }
        }

        if (!empty($data['event_gifts'])) {
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

        if (!empty($data['materials'])) {
            $event->materials()->sync($data['materials']);
        }

        if (!empty($data['office_events'])) {
            $event->officeEvents()->sync($data['office_events']);
        }

        if (!empty($data['clients'])) {
            $event->clients()->sync($data['clients']);
        }

        if (!empty($data['event_kits'])) {
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
}
