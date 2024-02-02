<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSectorOfficeRequest;
use App\Http\Requests\UpdateSectorOfficeRequest;
use App\Models\SectorOffice;
use App\Http\Resources\SectorOfficeResource;
use Illuminate\Http\Request;

class SectorOfficeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SectorOfficeController::class, 'sectorOffice');
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = SectorOffice::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $sectorOffices = $query->paginate($perPage);

        return response()->json([
            'data' => SectorOfficeResource::collection($sectorOffices),
            'pagination' => [
                'current_page' => $sectorOffices->currentPage(),
                'total_pages' => $sectorOffices->lastPage(),
                'per_page' => $sectorOffices->perPage(),
                'total' => $sectorOffices->total(),
            ],
            'soretable_fields' => SectorOffice::sortableFields()
        ], 200);
    }
    
    public function store(StoreSectorOfficeRequest $request)
    {
        $sectorOffice = SectorOffice::create($request->all());
        return response()->json(new SectorOfficeResource($sectorOffice), 201);
    }
    
    public function show(SectorOffice $sectorOffice)
    {
        return response()->json(new SectorOfficeResource($sectorOffice), 200);
    }
    
    public function update(UpdateSectorOfficeRequest $request, SectorOffice $sectorOffice)
    {
        $sectorOffice->update($request->all());
        return response()->json(new SectorOfficeResource($sectorOffice), 200);
    }
    
    public function destroy(SectorOffice $sectorOffice)
    {
        $sectorOffice->delete();
        return response()->json([], 204);
    }
}
