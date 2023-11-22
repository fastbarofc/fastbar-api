<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Http\Resources\EventResource;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::all();
        return response()->json(EventResource::collection($events), 200);
    }

    public function store(StoreEventRequest $request)
    {
        $event = Event::create($request->all());
        return response()->json(new EventResource($event), 201);
    }

    public function show(Event $event)
    {
        return response()->json(new EventResource($event), 200);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->all());
        return response()->json(new EventResource($event), 200);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json([], 204);
    }
}
