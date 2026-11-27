<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeEventRequest;
use App\Http\Requests\UpdateOfficeEventRequest;
use App\Http\Resources\OfficeEventResource;
use App\Models\Collaborator;
use App\Models\OfficeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Office\DetachOfficeCollaboratorRequest;
use App\Http\Requests\Office\SyncOfficeCollaboratorsRequest;
use Illuminate\Support\Facades\DB;

class OfficeEventController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(OfficeEventController::class, 'officeEvent');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = OfficeEvent::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
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
                'page' => $officeEvents->currentPage(),
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

    public function attachCollaborators(SyncOfficeCollaboratorsRequest $request, OfficeEvent $officeEvent)
    {
        $eventId = $request->integer('eventId');

        DB::transaction(function () use ($request, $officeEvent, $eventId) {
            $officeEvent->collaborators()->wherePivot('event_id', $eventId)->detach();

            foreach ($request->input('collaborators') as $collaboratorId) {
                $officeEvent->collaborators()->attach($collaboratorId, ['event_id' => $eventId]);
            }
        });

        return response()->json(['message' => 'Collaborators synced to office event'], 200);
    }

    public function detachCollaborator(DetachOfficeCollaboratorRequest $request, $officeEventId)
    {
        $officeEvent = OfficeEvent::findOrFail($officeEventId);
        $officeEvent->collaborators()
            ->wherePivot('event_id', $request->integer('event_id'))
            ->detach($request->integer('collaborator_id'));

        return response()->json(['message' => 'Collaborator detached successfully'], 200);
    }
}
