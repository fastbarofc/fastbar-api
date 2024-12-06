<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventGiftRequest;
use App\Http\Requests\UpdateEventGiftRequest;
use App\Http\Resources\EventGiftResource;
use App\Models\EventGift;
use Illuminate\Http\Request;

class EventGiftController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventGiftController::class, 'eventGift');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventGift::query();

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
        }

        $eventGifts = $query->paginate($perPage);

        return response()->json([
            'data' => EventGiftResource::collection($eventGifts),
            'pagination' => [
                'page' => $eventGifts->currentPage(),
                'total_pages' => $eventGifts->lastPage(),
                'per_page' => $eventGifts->perPage(),
                'total' => $eventGifts->total(),
            ],
            'sortable_fields' => EventGift::sortableFields(),
            'filterable_fields' => EventGift::filterableFields()
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
