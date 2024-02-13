<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(UnitController::class, 'unit');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Unit::query();

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

        $units = $query->paginate($perPage);

        return response()->json([
            'data' => UnitResource::collection($units),
            'pagination' => [
                'current_page' => $units->currentPage(),
                'total_pages' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
            'soretable_fields' => Unit::sortableFields()
        ], 200);
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
