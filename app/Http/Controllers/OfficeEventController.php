<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeEventRequest;
use App\Http\Requests\UpdateOfficeEventRequest;
use App\Http\Resources\OfficeEventResource;
use App\Models\Collaborator;
use App\Models\OfficeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $operator = $field == 'description' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if ($request->input('order_by')) {
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
        try {
            $officeEvent = OfficeEvent::create($request->all());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(new OfficeEventResource($officeEvent), 201);
    }

    public function show(OfficeEvent $officeEvent)
    {
        return response()->json(new OfficeEventResource($officeEvent), 200);
    }

    public function update(UpdateOfficeEventRequest $request, OfficeEvent $officeEvent)
    {
        try {
            $officeEvent->update($request->all());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(new OfficeEventResource($officeEvent), 200);
    }

    public function destroy(OfficeEvent $officeEvent)
    {
        $officeEvent->delete();
        return response()->json([], 204);
    }

    public function attachCollaborators(Request $request, OfficeEvent $officeEvent)
    {
        $collaboratorIds = $request->input('collaborators');
        $eventId = $request->input('eventId');

        $officeEvent->collaborators()->detach();

        foreach ($collaboratorIds as $collaboratorId) {
            $officeEvent->collaborators()->attach($collaboratorId, ['event_id' => $eventId]);
        }

        return response()->json(['message' => 'Collaborators synced to office event'], 200);
    }

    public function detachCollaborator(Request $request, $officeEventId)
    {
        $collaboratorId = $request->input('collaborator_id');
        $eventId = $request->input('event_id');

        $officeEvent = OfficeEvent::findOrFail($officeEventId);
        $officeEvent->collaborators()->detach($collaboratorId, ['event_id' => $eventId]);

        return response()->json(['message' => 'Collaborator detached successfully'], 200);
    }
}
