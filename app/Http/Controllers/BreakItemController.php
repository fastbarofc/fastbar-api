<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBreakItemRequest;
use App\Http\Requests\UpdateBreakItemRequest;
use App\Http\Resources\BreakItemResource;
use App\Models\BreakItem;
use Illuminate\Http\Request;

class BreakItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = BreakItem::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
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
}
