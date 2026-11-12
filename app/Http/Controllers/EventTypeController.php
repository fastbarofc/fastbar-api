<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventTypeRequest;
use App\Http\Requests\UpdateEventTypeRequest;
use App\Http\Resources\EventTypeResource;
use App\Models\EventType;
use Illuminate\Http\Request;

class EventTypeController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(EventTypeController::class, 'eventType');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventType::query()->select(['event_types.id', 'event_types.name']); // lista leve: sem o texto do contrato

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField($field, $value);
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventTypes = $query->paginate($perPage);

        return response()->json([
            'data' => EventTypeResource::collection($eventTypes),
            'pagination' => [
                'page' => $eventTypes->currentPage(),
                'total_pages' => $eventTypes->lastPage(),
                'per_page' => $eventTypes->perPage(),
                'total' => $eventTypes->total(),
            ],
            'sortable_fields' => EventType::sortableFields(),
            'filterable_fields' => EventType::filterableFields()
        ], 200);
    }

    public function store(StoreEventTypeRequest $request)
    {
        $eventType = EventType::create($request->all());
        return response()->json(new EventTypeResource($eventType), 201);

    }

    public function show(EventType $eventType)
    {
        return response()->json(new EventTypeResource($eventType), 200);
    }

    public function update(UpdateEventTypeRequest $request, EventType $eventType)
    {
        $eventType->update($request->all());
        return response()->json(new EventTypeResource($eventType), 200);
    }

    public function destroy(EventType $eventType)
    {
        $eventType->delete();
        return response()->json([], 204);
    }
}
