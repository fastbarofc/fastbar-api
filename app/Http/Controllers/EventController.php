<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventGiftResource;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\ExtraSaleResource;
use App\Http\Resources\EventKitResource;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::all();
        return response()->json(EventResource::collection($events), 200);
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->all();
        $event = Event::create($data);
        Event::create($request->all());

        dd($data['materials']);
        if(!empty($data['materials'])) {
            $event->materials()->sync($data['materials']);
        }

        if(!empty($data['office_events'])) {
            $event->officeEvents()->sync($data['office_events']);
        }
        
        dd($data['clients']);
        if(!empty($data['clients'])) {
            $event->clients()->sync($data['clients']);
        }

        return response()->json(new EventResource($event), 201);
    }

    public function show(Event $event)
    {
        return response()->json(new EventResource($event), 200);
    }
    
    public function update(UpdateEventRequest $request, Event $event)
    {
        $data = $request->all();

        if(!empty($data['materials'])) {
            $event->materials()->sync($data['materials']);
        }

        $event->update($request->all());
        return response()->json(new EventResource($event), 200);
    }
    
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json([], 204);
    }

    public function gifts(Event $event)
    {
        $gifts = $event->eventGifts;
        return response()->json(EventGiftResource::collection($gifts), 200);
    }

    public function materials(Event $event)
    {
        $materials = $event->materials;
        return response()->json(MaterialResource::collection($materials), 200);
    }
    
    public function extraSales(Event $event)
    {
        $extraSales = $event->extraSales;
        return response()->json(ExtraSaleResource::collection($extraSales), 200);
    }  

    public function eventKits(Event $event)
    {
        $eventKit = $event->eventKits;
        return response()->json(EventKitResource::collection($eventKit), 200);
    }
}
