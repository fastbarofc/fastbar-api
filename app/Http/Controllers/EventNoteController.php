<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventNoteRequest;
use App\Http\Requests\UpdateEventNoteRequest;
use App\Models\EventNote;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Resources\EventNoteResource;
use Illuminate\Support\Facades\Log;
use App\Services\EventHistoryService;

class EventNoteController extends Controller
{
    public function __construct(protected EventHistoryService $historyService)
    {
        //$this->authorizeResource(EventNoteController::class, 'eventNote'); TODO: Implementar
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventNote::query()->where('event_id', $request->event_id);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField($field, $value);
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $eventNotes = $query->paginate($perPage);

        return response()->json([
            'data' => EventNoteResource::collection($eventNotes),
            'pagination' => [
                'page' => $eventNotes->currentPage(),
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
        $data = $request->all();
        
        $event = Event::find($data['event_id']);
        if ($event) {
            $originalEventNotes = $event->eventNotes->toArray();
            
            $eventNote = EventNote::create($data);
            
            $event->refresh();
            $newEventNotes = $event->eventNotes->toArray();
            
            $originalData = ['event_notes' => $originalEventNotes];
            $requestData = ['event_notes' => $newEventNotes];
            
            $this->historyService->logEventUpdate($event, $originalData, $requestData);
        } else {
            $eventNote = EventNote::create($data);
        }
        
        return response()->json(new EventNoteResource($eventNote), 201);
    }

    public function show(EventNote $eventNote)
    {
        return response()->json(new EventNoteResource($eventNote), 200);
    }

    public function update(UpdateEventNoteRequest $request, EventNote $eventNote)
    {
        $event = $eventNote->event;
        
        if ($event) {
            $originalEventNotes = $event->eventNotes->toArray();
        }
        
        $data = $request->all();
        unset($data['collaborator_id']);
        $eventNote->update($data);
        
        if ($event) {
            $event->refresh();
            $newEventNotes = $event->eventNotes->toArray();
            
            $originalData = ['event_notes' => $originalEventNotes];
            $requestData = ['event_notes' => $newEventNotes];
            
            $this->historyService->logEventUpdate($event, $originalData, $requestData);
        }
        
        return response()->json(new EventNoteResource($eventNote), 200);
    }

    public function destroy(EventNote $eventNote)
    {
        $event = $eventNote->event;
        
        if ($event) {
            $originalEventNotes = $event->eventNotes->toArray();
        }
        
        $eventNote->delete();
        
        if ($event) {
            $event->refresh();
            $newEventNotes = $event->eventNotes->toArray();
            
            $originalData = ['event_notes' => $originalEventNotes];
            $requestData = ['event_notes' => $newEventNotes];
            
            $this->historyService->logEventUpdate($event, $originalData, $requestData);
        }
        
        return response()->json([], 204);
    }
}