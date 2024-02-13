<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventCaptureRequest;
use App\Http\Requests\UpdateEventCaptureRequest;
use App\Models\EventCapture;
use App\Http\Resources\EventCaptureResource;
use Illuminate\Http\Request;

class EventCaptureController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventCaptureController::class, 'eventCapture');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventCapture::query();

        if ($request->input('filter_by')) {
            $filterableArray = $request->input('filter_by');
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventCaptures = $query->paginate($perPage);

        return response()->json([
            'data' => EventCaptureResource::collection($eventCaptures),
            'pagination' => [
                'current_page' => $eventCaptures->currentPage(),
                'total_pages' => $eventCaptures->lastPage(),
                'per_page' => $eventCaptures->perPage(),
                'total' => $eventCaptures->total(),
            ],
            'sortable_fields' => EventCapture::sortableFields()
        ], 200);
    }

    public function store(StoreEventCaptureRequest $request)
    {
        $eventCapture = EventCapture::create($request->all());
        return response()->json(new EventCaptureResource($eventCapture), 201);
    }

    public function show(EventCapture $eventCapture)
    {
        return response()->json(new EventCaptureResource($eventCapture), 200);
    }

    public function update(UpdateEventCaptureRequest $request, EventCapture $eventCapture)
    {
        $eventCapture->update($request->all());
        return response()->json(new EventCaptureResource($eventCapture), 200);
    }

    public function destroy(EventCapture $eventCapture)
    {
        $eventCapture->delete();
        return response()->json([], 204);
    }
}
