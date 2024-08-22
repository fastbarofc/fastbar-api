<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollaboratorIncidentRequest;
use App\Http\Requests\UpdateCollaboratorIncidentRequest;
use App\Http\Resources\CollaboratorIncidentResource;
use App\Models\CollaboratorIncident;
use Illuminate\Http\Request;

class CollaboratorIncidentController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(CollaboratorIncident::class, 'collaboratorIncident');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = CollaboratorIncident::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $query->filterByField(
                        field: $field,
                        value: $value,
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $collaboratorIncidents = $query->paginate($perPage);

        return response()->json([
            'data' => CollaboratorIncidentResource::collection($collaboratorIncidents),
            'pagination' => [
                'current_page' => $collaboratorIncidents->currentPage(),
                'total_pages' => $collaboratorIncidents->lastPage(),
                'per_page' => $collaboratorIncidents->perPage(),
                'total' => $collaboratorIncidents->total(),
            ],
            'sortable_fields' => CollaboratorIncident::sortableFields(),
            'filterable_fields' => CollaboratorIncident::filterableFields()
        ], 200);
    }

    public function store(StoreCollaboratorIncidentRequest $request)
    {
        $collaboratorIncident = CollaboratorIncident::create($request->all());
        return response()->json(new CollaboratorIncidentResource($collaboratorIncident), 201);
    }

    public function show(CollaboratorIncident $collaboratorIncident)
    {
        return response()->json(new CollaboratorIncidentResource($collaboratorIncident), 200);
    }

    public function update(UpdateCollaboratorIncidentRequest $request, CollaboratorIncident $collaboratorIncident)
    {
        $collaboratorIncident->update($request->all());
        return response()->json(new CollaboratorIncidentResource($collaboratorIncident), 200);
    }

    public function destroy(CollaboratorIncident $collaboratorIncident)
    {
        $collaboratorIncident->delete();
        return response()->json([], 204);
    }
}
