<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MaterialController::class, 'material');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Material::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $materials = $query->paginate($perPage);

        return response()->json([
            'data' => MaterialResource::collection($materials),
            'pagination' => [
                'current_page' => $materials->currentPage(),
                'total_pages' => $materials->lastPage(),
                'per_page' => $materials->perPage(),
                'total' => $materials->total(),
            ],
            'sortable_fields' => Material::sortableFields(),
            'filterable_fields' => Material::filterableFields()
        ], 200);
    }

    public function store(StoreMaterialRequest $request)
    {
        $material = Material::create($request->all());
        return response()->json(new MaterialResource($material), 201);
    }

    public function show(Material $material)
    {
        return response()->json(new MaterialResource($material), 200);
    }

    public function update(UpdateMaterialRequest $request, Material $material)
    {
        $material->update($request->all());
        return response()->json(new MaterialResource($material), 200);
    }

    public function destroy(Material $material)
    {
        $material->delete();
        return response()->json([], 204);
    }
}
