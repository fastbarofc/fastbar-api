<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;
use App\Http\Resources\OfficeResource;

class OfficeController extends Controller
{
    
    public function index()
    {
        $offices = Office::all();
        return response()->json(OfficeResource::collection($offices), 200);
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
