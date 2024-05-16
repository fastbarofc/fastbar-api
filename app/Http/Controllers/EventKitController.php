<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventKitTreeResource;
use App\Models\EventKit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventKitController extends Controller
{
    public function __construct()
    {
        //$this->authorizeResource(EventKitController::class, 'event_kit'); TODO: Implementar
    }

    public function index(Request $request)
    {   

        $query = EventKit::query();

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
        }

        $query->whereNull('event_kit_id');

        $eventKits = $query->get();

        
        return response()->json([
            'data' => EventKitTreeResource::collection($eventKits),
        ], 200);
    }

    public function children(EventKit $kit)
    {
        $children = $kit->children;
        return response()->json($children, 200);
    }

    public function store(Request $request)
    {
        $eventKit = EventKit::create($request->all());
        return response()->json(new EventKitTreeResource($eventKit), 201);
    }

    public function show(EventKit $eventKit)
    {
        return response()->json(new EventKitTreeResource($eventKit), 200);
    }

    public function update(Request $request, EventKit $eventKit)
    {
        $eventKit->update($request->all());
        return response()->json(new EventKitTreeResource($eventKit), 200);
    }

    public function destroy(EventKit $eventKit)
    {
        $eventKit->delete();
        return response()->json([], 204);
    }
}
