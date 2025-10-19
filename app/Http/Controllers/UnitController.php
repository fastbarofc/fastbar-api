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
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(UnitController::class, 'unit');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Unit::query();

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

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        if ($perPage != 0) {
            $units = $query->paginate($perPage);
            $pagination = [
                'page' => $units->currentPage(),
                'total_pages' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ];
        } else {
            $units = $query->get();
            $pagination = null;
        }

        return response()->json([
            'data' => UnitResource::collection($units),
            'pagination' => $pagination,
            'sortable_fields' => Unit::sortableFields(),
            'filterable_fields' => Unit::filterableFields()
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
