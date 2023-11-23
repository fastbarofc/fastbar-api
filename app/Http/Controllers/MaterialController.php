<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\Material;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::all();
        return response()->json(MaterialResource::collection($materials), 200);
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
