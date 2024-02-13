<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectorRequest;
use App\Http\Requests\UpdateSectorRequest;
use App\Models\Sector;
use App\Http\Resources\SectorResource;
use App\Http\Resources\SectorOfficeResource;
use App\Models\SectorOffice;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SectorController::class, 'sector');
    }

    public function index(Request $request)
    {
        $query = Sector::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $sectors = $query->get();

        return response()->json([
            'data' => SectorResource::collection($sectors),
            'soretable_fields' => Sector::sortableFields()
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

    public function sectorOffices(Request $request, Sector $sector)
    {
        $this->authorize('view', $sector);

        $perPage = $request->input('per_page', 10);

        $query = Sector::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $sectorOffices = $query->where('sector_id', $sector->id)->paginate($perPage);

        return response()->json([
            'data' => SectorOfficeResource::collection($sectorOffices),
            'pagination' => [
                'current_page' => $sectorOffices->currentPage(),
                'total_pages' => $sectorOffices->lastPage(),
                'per_page' => $sectorOffices->perPage(),
                'total' => $sectorOffices->total(),
            ],
        ], 200);
    }
}
