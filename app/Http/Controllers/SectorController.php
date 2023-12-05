<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectorRequest;
use App\Http\Requests\UpdateSectorRequest;
use App\Models\Sector;
use App\Http\Resources\SectorResource;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use Illuminate\Http\Request;

class SectorController extends Controller
{
  
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sectors = Sector::paginate($perPage);

        return response()->json([
            'data' => SectorResource::collection($sectors),
            'pagination' => [
                'current_page' => $sectors->currentPage(),
                'total_pages' => $sectors->lastPage(),
                'per_page' => $sectors->perPage(),
                'total' => $sectors->total(),
            ],
        ], 200);
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

    public function offices(Request $request, Sector $sector)
    {
        $perPage = $request->input('per_page', 10);
        $offices = Office::where('sector_id', $sector->id)->paginate($perPage);

        return response()->json([
            'data' => OfficeResource::collection($offices),
            'pagination' => [
                'current_page' => $offices->currentPage(),
                'total_pages' => $offices->lastPage(),
                'per_page' => $offices->perPage(),
                'total' => $offices->total(),
            ],
        ], 200);
    }
}
