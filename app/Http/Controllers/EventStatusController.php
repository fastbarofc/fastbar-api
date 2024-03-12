<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventStatusRequest;
use App\Http\Requests\UpdateEventStatusRequest;
use App\Http\Resources\EventStatusResource;
use App\Models\EventStatus;
use Illuminate\Http\Request;

class EventStatusController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventStatusController::class, 'eventStatus');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventStatus::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventStatuses = $query->paginate($perPage);

        return response()->json([
            'data' => EventStatusResource::collection($eventStatuses),
            'pagination' => [
                'current_page' => $eventStatuses->currentPage(),
                'total_pages' => $eventStatuses->lastPage(),
                'per_page' => $eventStatuses->perPage(),
                'total' => $eventStatuses->total(),
            ],
            'sortable_fields' => EventStatus::sortableFields(),
            'filterable_fields' => EventStatus::filterableFields()
        ], 200);
    }

    public function store(StoreEventStatusRequest $request)
    {
        $eventStatus = EventStatus::create($request->all());
        return response()->json(new EventStatusResource($eventStatus), 201);
    }

    public function show(EventStatus $eventStatus)
    {
        return response()->json(new EventStatusResource($eventStatus), 200);
    }

    public function update(UpdateEventStatusRequest $request, EventStatus $eventStatus)
    {
        $eventStatus->update($request->all());
        return response()->json(new EventStatusResource($eventStatus), 200);
    }

    public function destroy(EventStatus $eventStatus)
    {
        $eventStatus->delete();
        return response()->json([], 204);
    }
}
