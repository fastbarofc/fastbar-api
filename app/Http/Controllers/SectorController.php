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
    $perPage = $request->input('per_page', 10);

    $query = Sector::query();

    if ($request->input('filter_by')) {
        $filterable = $request->input('filter_by');
        if ($filterable && $filterable !== '[]') {
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }
    }

    if ($request->input('order_by')) {
        $sorteableField = $request->input('order_by');
        $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
        $query->orderByField($sorteableField, $direction);
    }

    if ($perPage != 0) {
        $sectors = $query->paginate($perPage);
        $pagination = [
            'page' => $sectors->currentPage(),
            'total_pages' => $sectors->lastPage(),
            'per_page' => $sectors->perPage(),
            'total' => $sectors->total(),
        ];
    } else {
        $sectors = $query->get();
        $pagination = null;
    }

    return response()->json([
        'data' => SectorResource::collection($sectors),
        'pagination' => $pagination,
        'sortable_fields' => Sector::sortableFields(),
        'filterable_fields' => Sector::filterableFields()
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
                'page' => $sectorOffices->currentPage(),
                'total_pages' => $sectorOffices->lastPage(),
                'per_page' => $sectorOffices->perPage(),
                'total' => $sectorOffices->total(),
            ],
        ], 200);
    }
}
