<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::all();
        return response()->json(UnitResource::collection($units), 200);
    }

    public function store(StoreUnitRequest $request)
    {
        $unit = Unit::create($request->all());
        return response()->json(new UnitResource($unit), 201);
    }

    public function show(Unit $unit)
    {
        return response()->json(new UnitResource($unit), 200);
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $unit->update($request->all());
        return response()->json(new UnitResource($unit), 200);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return response()->json([], 204);
    }
}
