<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeEventRequest;
use App\Http\Requests\UpdateOfficeEventRequest;
use App\Http\Resources\OfficeEventResource;
use App\Models\OfficeEvent;

class OfficeEventController extends Controller
{
    public function index()
    {
        $officeEvents = OfficeEvent::all();
        return response()->json(OfficeEventResource::collection($officeEvents), 200);
    }

    public function store(StoreOfficeEventRequest $request)
    {
        $officeEvent = OfficeEvent::create($request->all());
        return response()->json(new OfficeEventResource($officeEvent), 201);
    }

    public function show(OfficeEvent $officeEvent)
    {
        return response()->json(new OfficeEventResource($officeEvent), 200);
    }

    public function update(UpdateOfficeEventRequest $request, OfficeEvent $officeEvent)
    {
        $officeEvent->update($request->all());
        return response()->json(new OfficeEventResource($officeEvent), 200);
    }

    public function destroy(OfficeEvent $officeEvent)
    {
        $officeEvent->delete();
        return response()->json([], 204);
    }
}
