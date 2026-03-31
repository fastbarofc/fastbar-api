<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCostCenterRequest;
use App\Http\Requests\UpdateCostCenterRequest;
use App\Http\Resources\CostCenterResource;
use App\Models\CostCenter;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(CostCenter::class, 'costCenter');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = CostCenter::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];

                    if ($field === 'search') {
                        if (!empty($value)) {
                            $query->where(function ($subQuery) use ($value) {
                                foreach (CostCenter::searchableFields() as $searchableField) {
                                    $subQuery->orWhere('cost_centers.' . $searchableField, 'like', "%$value%");
                                }
                            });
                        }
                    } else {
                        $operator = $field == 'description' ? 'like' : '=';
                        $query->where($field, $operator, $operator == 'like' ? "%$value%" : $value);
                    }
                }
            }
        }

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortableField, $direction);
        }

        $costCenters = $query->paginate($perPage);

        return response()->json([
            'data' => CostCenterResource::collection($costCenters),
            'pagination' => [
                'page' => $costCenters->currentPage(),
                'total_pages' => $costCenters->lastPage(),
                'per_page' => $costCenters->perPage(),
                'total' => $costCenters->total(),
            ],
            'sortable_fields' => ['code', 'description', 'account'],
            'filterable_fields' => ['code', 'description', 'account'],
        ], 200);
    }

    public function store(StoreCostCenterRequest $request)
    {
        $costCenter = CostCenter::create($request->all());
        return response()->json(new CostCenterResource($costCenter), 201);
    }

    public function show(CostCenter $costCenter)
    {
        return response()->json(new CostCenterResource($costCenter), 200);
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter)
    {
        $costCenter->update($request->all());
        return response()->json(new CostCenterResource($costCenter), 200);
    }

    public function destroy(CostCenter $costCenter)
    {
        $costCenter->delete();
        return response()->json([], 204);
    }
}