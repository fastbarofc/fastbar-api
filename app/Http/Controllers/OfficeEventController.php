<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeEventRequest;
use App\Http\Requests\UpdateOfficeEventRequest;
use App\Http\Resources\OfficeEventResource;
use App\Models\OfficeEvent;
use Illuminate\Http\Request;

class OfficeEventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OfficeEventController::class, 'officeEvent');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = OfficeEvent::query();

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

        $officeEvents = $query->paginate($perPage);

        return response()->json([
            'data' => OfficeEventResource::collection($officeEvents),
            'pagination' => [
                'current_page' => $officeEvents->currentPage(),
                'total_pages' => $officeEvents->lastPage(),
                'per_page' => $officeEvents->perPage(),
                'total' => $officeEvents->total(),
            ],
            'sortable_fields' => OfficeEvent::sortableFields(),
            'filterable_fields' => OfficeEvent::filterableFields()
        ], 200);
    }

    public function store(StoreOfficeEventRequest $request)
    {
        $officeEvent = OfficeEvent::create($request->all());
        return response()->json(new OfficeEventResource($officeEvent), 201);
    }

    public function show(OfficeEvent $officeEvent)
    {
        return response()->json(new OfficeEventResource($officeEvent), 200);
    }

    public function update(UpdateOfficeEventRequest $request, OfficeEvent $officeEvent)
    {
        $officeEvent->update($request->all());
        return response()->json(new OfficeEventResource($officeEvent), 200);
    }

    public function destroy(OfficeEvent $officeEvent)
    {
        $officeEvent->delete();
        return response()->json([], 204);
    }
}
