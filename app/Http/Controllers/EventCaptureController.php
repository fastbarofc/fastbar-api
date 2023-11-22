<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventCaptureRequest;
use App\Http\Requests\UpdateEventCaptureRequest;
use App\Models\EventCapture;
use App\Http\Resources\EventCaptureResource;

class EventCaptureController extends Controller
{
    public function index()
    {
        $eventCaptures = EventCapture::all();
        return response()->json(EventCaptureResource::collection($eventCaptures), 200);
    }

    public function store(StoreEventCaptureRequest $request)
    {
        $eventCapture = EventCapture::create($request->all());
        return response()->json(new EventCaptureResource($eventCapture), 201);
    }

    public function show(EventCapture $eventCapture)
    {
        return response()->json(new EventCaptureResource($eventCapture), 200);
    }

    public function update(UpdateEventCaptureRequest $request, EventCapture $eventCapture)
    {
        $eventCapture->update($request->all());
        return response()->json(new EventCaptureResource($eventCapture), 200);
    }

    public function destroy(EventCapture $eventCapture)
    {
        $eventCapture->delete();
        return response()->json([], 204);
    }
}
