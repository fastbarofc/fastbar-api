<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventNoteRequest;
use App\Http\Requests\UpdateEventNoteRequest;
use App\Models\EventNote;
use Illuminate\Http\Request;
use App\Http\Resources\EventNoteResource;

class EventNoteController extends Controller
{
    public function __construct()
    {
        //$this->authorizeResource(EventNoteController::class, 'eventNote'); TODO: Implementar
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventNote::query();

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

        $eventNotes = $query->paginate($perPage);

        return response()->json([
            'data' => EventNoteResource::collection($eventNotes),
            'pagination' => [
                'current_page' => $eventNotes->currentPage(),
                'total_pages' => $eventNotes->lastPage(),
                'per_page' => $eventNotes->perPage(),
                'total' => $eventNotes->total(),
            ],
            'sortable_fields' => EventNote::sortableFields(),
            'filterable_fields' => EventNote::filterableFields()
        ], 200);
    }

    public function store(StoreEventNoteRequest $request)
    {
        $user = auth()->user();
        $collaborator = $user->collaborator;
        $data = $request->all();
        $data['collaborator_id'] = $collaborator->id;
        $eventNote = EventNote::create($data);
        return response()->json(new EventNoteResource($eventNote), 201);
    }

    public function show(EventNote $eventNote)
    {
        return response()->json(new EventNoteResource($eventNote), 200);
    }

    public function update(UpdateEventNoteRequest $request, EventNote $eventNote)
    {
        $data = $request->all();
        unset($data['collaborator_id']);
        $eventNote->update($data);
        return response()->json(new EventNoteResource($eventNote), 200);
    }

    public function destroy(EventNote $eventNote)
    {
        $eventNote->delete();
        return response()->json([], 204);
    }
}