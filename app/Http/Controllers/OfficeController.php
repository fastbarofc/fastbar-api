<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;
use App\Http\Resources\OfficeResource;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OfficeController::class, 'office');
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Office::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $offices = $query->paginate($perPage);

        return response()->json([
            'data' => OfficeResource::collection($offices),
            'pagination' => [
                'current_page' => $offices->currentPage(),
                'total_pages' => $offices->lastPage(),
                'per_page' => $offices->perPage(),
                'total' => $offices->total(),
            ],
            'soretable_fields' => Office::sortableFields()
        ], 200);
    }
    
    public function store(StoreOfficeRequest $request)
    {
        $office = Office::create($request->all());
        return response()->json(new OfficeResource($office), 201);
    }
    
    public function show(Office $office)
    {
        return response()->json(new OfficeResource($office), 200);
    }
    
    public function update(UpdateOfficeRequest $request, Office $office)
    {
        $office->update($request->all());
        return response()->json(new OfficeResource($office), 200);
    }
    
    public function destroy(Office $office)
    {
        $office->delete();
        return response()->json([], 204);
    }
}
