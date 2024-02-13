<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlockedDateRequest;
use App\Http\Requests\UpdateBlockedDateRequest;
use App\Models\BlockedDate;
use App\Http\Resources\BlockedDateResource;
use Illuminate\Http\Request;

class BlockedDateController extends Controller
{
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = BlockedDate::query();

        if ($request->input('filter_by')) {
            $filterableArray = $request->input('filter_by');
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $blockedDates = $query->paginate($perPage);

        return response()->json([
            'data' => BlockedDateResource::collection($blockedDates),                
            'pagination' => [
                'current_page' => $blockedDates->currentPage(),
                'total_pages' => $blockedDates->lastPage(),
                'per_page' => $blockedDates->perPage(),
                'total' => $blockedDates->total(),
            ],
            'soretable_fields' => BlockedDate::sortableFields()
        ], 200);
    }
    
    public function store(StoreBlockedDateRequest $request)
    {
        $data = $request->all();
        $blockedDate = BlockedDate::create($data);
        return response()->json(new BlockedDateResource($blockedDate), 201);
    }
    
    public function show(BlockedDate $blockedDate)
    {
        return response()->json(new BlockedDateResource($blockedDate), 200);
    }
    
    public function update(UpdateBlockedDateRequest $request, BlockedDate $blockedDate)
    {
        $data = $request->all();
        $blockedDate->update($data);
        return response()->json(new BlockedDateResource($blockedDate), 200);
    }
    
    public function destroy(BlockedDate $blockedDate)
    {
        $blockedDate->delete();
        return response()->json(null, 204);
    }
}
