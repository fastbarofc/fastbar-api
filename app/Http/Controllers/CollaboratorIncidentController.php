<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollaboratorIncidentRequest;
use App\Http\Requests\UpdateCollaboratorIncidentRequest;
use App\Http\Resources\CollaboratorIncidentResource;
use App\Models\CollaboratorIncident;
use Illuminate\Http\Request;

class CollaboratorIncidentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $collaboratorIncidents = CollaboratorIncident::paginate($perPage);

        return response()->json([
            'data' => CollaboratorIncidentResource::collection($collaboratorIncidents),
            'pagination' => [
                'current_page' => $collaboratorIncidents->currentPage(),
                'total_pages' => $collaboratorIncidents->lastPage(),
                'per_page' => $collaboratorIncidents->perPage(),
                'total' => $collaboratorIncidents->total(),
            ],
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
