<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\UpdateKitRequest;
use App\Http\Resources\KitResource;
use App\Http\Resources\KitTreeResource;
use App\Models\EventKit;
use Illuminate\Http\Request;

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
            'data' => KitTreeResource::collection($eventKits),
            'sortable_fields' => EventKit::sortableFields(),
            'filterable_fields' => EventKit::filterableFields()
        ], 200);
    }

    public function children(EventKit $kit)
    {
        $children = $kit->children;
        return response()->json($children, 200);
    }
}
