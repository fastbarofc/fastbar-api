<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventTypeRequest;
use App\Http\Requests\UpdateEventTypeRequest;
use App\Http\Resources\EventTypeResource;
use App\Models\EventType;

class EventTypeController extends Controller
{
    public function index()
    {
        $eventTypes = EventType::all();
        return response()->json(EventTypeResource::collection($eventTypes), 200);
    }

    public function store(StoreEventTypeRequest $request)
    {
        $eventType = EventType::create($request->all());
        return response()->json(new EventTypeResource($eventType), 201);

    }

    public function show(EventType $eventType)
    {
        return response()->json(new EventTypeResource($eventType), 200);
    }

    public function update(UpdateEventTypeRequest $request, EventType $eventType)
    {
        $eventType->update($request->all());
        return response()->json(new EventTypeResource($eventType), 200);
    }

    public function destroy(EventType $eventType)
    {
        $eventType->delete();
        return response()->json([], 204);
    }
}
