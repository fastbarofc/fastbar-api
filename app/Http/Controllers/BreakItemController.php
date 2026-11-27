<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBreakItemRequest;
use App\Http\Requests\UpdateBreakItemRequest;
use App\Http\Resources\BreakItemResource;
use App\Models\BreakItem;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'description' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $value,
                        operator: $operator
                    );
                }
            }
        }

        $query->with('unit');

        if ($request->input('order_by')) {
            $sortField = $request->input('order_by');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
    
            if (str_contains($sortField, '.') && BreakItem::isFieldSortable($sortField)) {
                [$relation, $field] = explode('.', $sortField);
                $query->whereHas($relation)->with($relation)->orderByRelation($relation, $field, $direction);
            } else {
                $query->orderByField($sortField, $direction);
            }
        }

        $breakItems = $query->paginate($perPage);

        return response()->json([
            'data' => BreakItemResource::collection($breakItems),
            'pagination' => [
                'page' => $breakItems->currentPage(),
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
        $request->validate([
            'breakItems' => 'nullable|array',
            'breakItems.*.id' => 'required|integer|exists:break_items,id',
            'breakItems.*.quantity' => 'required|integer|min:0|max:2147483647',
            'breakItems.*.value' => 'required|numeric|min:0|max:99999999.99',
            'breakItems.*.unit_id' => 'required|integer|exists:units,id',
        ], [
            'breakItems.*.id' => 'Um dos itens de quebra informados não existe.',
            'breakItems.*.quantity' => 'Informe uma quantidade válida em todos os itens de quebra.',
            'breakItems.*.value' => 'Informe um valor válido em todos os itens de quebra.',
            'breakItems.*.unit_id' => 'Informe a unidade de todos os itens de quebra.',
        ]);

        DB::transaction(function () use ($event, $request) {
            foreach ($request->input('breakItems', []) as $breakItem) {
                $event->breakItems()->syncWithoutDetaching([
                    $breakItem['id'] => [
                        'quantity' => $breakItem['quantity'],
                        'value' => $breakItem['value'],
                        'unit_id' => $breakItem['unit_id'],
                    ],
                ]);
            }
        });

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
