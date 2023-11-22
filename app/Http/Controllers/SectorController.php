<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectorRequest;
use App\Http\Requests\UpdateSectorRequest;
use App\Models\Sector;
use App\Http\Resources\SectorResource;
use App\Http\Resources\OfficeResource;

class SectorController extends Controller
{
  
    public function index()
    {
        $sectors = Sector::all();
        return response()->json(SectorResource::collection($sectors), 200);
    }
    
    public function store(StoreSectorRequest $request)
    {
        $sector = Sector::create($request->all());
        return response()->json(new SectorResource($sector), 201);
    }
    
    public function show(Sector $sector)
    {
        return response()->json(new SectorResource($sector), 200);
    }
    
    public function update(UpdateSectorRequest $request, Sector $sector)
    {
        $sector->update($request->all());
        return response()->json(new SectorResource($sector), 200);
    }
    
    public function destroy(Sector $sector)
    {
        $sector->delete();
        return response([], 204);
    }

    public function offices(Sector $sector)
    {
        $offices = $sector->offices;
        return response()->json(OfficeResource::collection($offices), 200);
    }
}
