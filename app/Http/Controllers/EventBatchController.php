<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventBatchRequest;
use App\Http\Requests\UpdateEventBatchRequest;
use App\Http\Resources\EventBatchResource;
use Illuminate\Http\Request;
use App\Models\EventBatch;

class EventBatchController extends Controller
{
    public function __construct()
    {
        //$this->authorizeResource(EventbatchController::class, 'eventBatch'); TODO: Implementar
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventBatch::query()->where('event_id', $request->event_id);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $eventBatches = $query->paginate($perPage);

        return response()->json([
            'data' => EventBatchResource::collection($eventBatches),
            'pagination' => [
                'current_page' => $eventBatches->currentPage(),
                'total_pages' => $eventBatches->lastPage(),
                'per_page' => $eventBatches->perPage(),
                'total' => $eventBatches->total(),
            ],
            'sortable_fields' => EventBatch::sortableFields(),
            'filterable_fields' => EventBatch::filterableFields()
        ], 200);
    }

    public function store(StoreEventBatchRequest $request)
    {
        $eventBatch = EventBatch::create($request->all());
        return response()->json(new EventBatchResource($eventBatch), 201);
    }

    public function show(EventBatch $eventBatch)
    {
        return response()->json(new EventBatchResource($eventBatch), 200);
    }

    public function update(UpdateEventBatchRequest $request, EventBatch $eventBatch)
    {
        $eventBatch->update($request->all());
        return response()->json(new EventBatchResource($eventBatch), 200);
    }

    public function destroy(EventBatch $eventBatch)
    {
        $eventBatch->delete();
        return response()->json([], 204);
    }
}
