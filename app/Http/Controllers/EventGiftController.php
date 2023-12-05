<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventGiftRequest;
use App\Http\Requests\UpdateEventGiftRequest;
use App\Http\Resources\EventGiftResource;
use App\Models\EventGift;
use Illuminate\Http\Request;

class EventGiftController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $eventGifts = EventGift::paginate($perPage);

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

    public function store(StoreEventGiftRequest $request)
    {
        $eventGift = EventGift::create($request->all());
        return response()->json(new EventGiftResource($eventGift), 201);
    }

    public function show(EventGift $eventGift)
    {
        return response()->json(new EventGiftResource($eventGift), 200);
    }

    public function update(UpdateEventGiftRequest $request, EventGift $eventGift)
    {
        $eventGift->update($request->all());
        return response()->json(new EventGiftResource($eventGift), 200);
    }

    public function destroy(EventGift $eventGift)
    {
        $eventGift->delete();
        return response()->json([], 204);
    }
}
