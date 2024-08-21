<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBreakItemRequest;
use App\Http\Requests\UpdateBreakItemRequest;
use App\Http\Resources\BreakItemResource;
use App\Models\BreakItem;
use App\Models\Event;
use Illuminate\Http\Request;

class BreakItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = BreakItem::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $operator = $field == 'description' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $value,
                        operator: $operator
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $breakItems = $query->paginate($perPage);

        return response()->json([
            'data' => BreakItemResource::collection($breakItems),
            'pagination' => [
                'current_page' => $breakItems->currentPage(),
                'total_pages' => $breakItems->lastPage(),
                'per_page' => $breakItems->perPage(),
                'total' => $breakItems->total(),
            ],
            'sortable_fields' => BreakItem::sortableFields(),
            'filterable_fields' => BreakItem::filterableFields()
        ], 200);
    }

    public function store(StoreBreakItemRequest $request)
    {
        $data = $request->all();
        $breakItem = BreakItem::create($data);
        return response()->json(new BreakItemResource($breakItem), 201);
    }

    public function show(BreakItem $breakItem)
    {
        return response()->json(new BreakItemResource($breakItem), 200);
    }

    public function update(UpdateBreakItemRequest $request, BreakItem $breakItem)
    {
        $data = $request->all();
        $breakItem->update($data);
        return response()->json(new BreakItemResource($breakItem), 200);
    }

    public function destroy(BreakItem $breakItem)
    {
        $breakItem->delete();
        return response()->json(null, 204);
    }

    public function attachBreakItemsToEvent(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);
        $breakItems = $request->input('breakItems', []);

        foreach ($breakItems as $breakItem) {
            $event->breakItems()->syncWithoutDetaching([
                $breakItem['id'] => [
                    'quantity' => $breakItem['quantity'],
                    'value' => $breakItem['value'],
                    'unit_id' => $breakItem['unit_id']
                ]
            ]);
        }

        return response()->json(['message' => 'Itens de quebra associados ao evento com sucesso']);
    }

    public function detachBreakItemFromEvent($eventId, $breakItemId)
    {
        $event = Event::findOrFail($eventId);
        $event->breakItems()->detach($breakItemId);

        return response()->json(['message' => 'Item de quebra desassociado do evento com sucesso']);
    }

    public function getEventBreakItems($eventId)
    {
        $event = Event::with('breakItems')->findOrFail($eventId);
        return response()->json($event->breakItems);
    }
}
