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
use App\Models\EventGift;
use App\Models\Material;
use App\Models\ExtraSale;
use App\Models\EventKit;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $events = Event::paginate($perPage);

        return response()->json([
            'data' => EventResource::collection($events),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'total_pages' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ], 200);
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

    public function gifts(Request $request, Event $event)
    {
        $perPage = $request->input('per_page', 10);
        $eventGifts = EventGift::where('event_id', $event->id)->paginate($perPage);

        return response()->json([
            'data' => EventGiftResource::collection($eventGifts),
            'pagination' => [
                'current_page' => $eventGifts->currentPage(),
                'total_pages' => $eventGifts->lastPage(),
                'per_page' => $eventGifts->perPage(),
                'total' => $eventGifts->total(),
            ],
        ], 200);
    }

    public function materials(Request $request, Event $event)
    {
        $perPage = $request->input('per_page', 10);
        $materialIds = $event->materials->pluck('id');
        $materials = Material::where('id', $materialIds[0])->paginate($perPage);

        return response()->json([
            'data' => MaterialResource::collection($materials),
            'pagination' => [
                'current_page' => $materials->currentPage(),
                'total_pages' => $materials->lastPage(),
                'per_page' => $materials->perPage(),
                'total' => $materials->total(),
            ],
        ], 200);
    }
    
    public function extraSales(Request $request, Event $event)
    {
        $perPage = $request->input('per_page', 10);
        $extraSales = ExtraSale::where('event_id', $event->id)->paginate($perPage);

        return response()->json([
            'data' => ExtraSaleResource::collection($extraSales),
            'pagination' => [
                'current_page' => $extraSales->currentPage(),
                'total_pages' => $extraSales->lastPage(),
                'per_page' => $extraSales->perPage(),
                'total' => $extraSales->total(),
            ],
        ], 200);
    }  

    public function eventKits(Request $request, Event $event)
    {
        $perPage = $request->input('per_page', 10);
        $eventKit = EventKit::where('event_id', $event->id)->paginate($perPage);

        return response()->json([
            'data' => EventKitResource::collection($eventKit),
            'pagination' => [
                'current_page' => $eventKit->currentPage(),
                'total_pages' => $eventKit->lastPage(),
                'per_page' => $eventKit->perPage(),
                'total' => $eventKit->total(),
            ],
        ], 200);
    }
}
