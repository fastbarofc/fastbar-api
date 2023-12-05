<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $materials = Material::paginate($perPage);

        return response()->json([
            'data' => MaterialResource::collection($materials),
            'pagination' => [
                'current_page' => $materials->currentPage(),
                'total_pages' => $materials->lastPage(),
                'per_page' => $materials->perPage(),
                'total' => $materials->total(),
            ],
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
