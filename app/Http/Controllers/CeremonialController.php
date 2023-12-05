<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCeremonialRequest;
use App\Http\Requests\UpdateCeremonialRequest;
use App\Http\Resources\CeremonialResource;
use App\Models\Ceremonial;
use Illuminate\Http\Request;

class CeremonialController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $ceremonials = Ceremonial::paginate($perPage);

            return response()->json([
                'data'       => CeremonialResource::collection($ceremonials),
                'pagination' => [
                    'current_page' => $ceremonials->currentPage(),
                    'total_pages'  => $ceremonials->lastPage(),
                    'per_page'     => $ceremonials->perPage(),
                    'total'        => $ceremonials->total(),
                ],
            ], 200);
    }

    public function store(StoreCeremonialRequest $request)
    {
        $ceremonial = Ceremonial::create($request->all());
        return response()->json(new CeremonialResource($ceremonial), 201);
    }

    public function show(Ceremonial $ceremonial)
    {
        return response()->json(new CeremonialResource($ceremonial), 200);
    }

    public function update(UpdateCeremonialRequest $request, Ceremonial $ceremonial)
    {
        $ceremonial->update($request->all());
        return response()->json(new CeremonialResource($ceremonial), 200);
    }

    public function destroy(Ceremonial $ceremonial)
    {
        $ceremonial->delete();
        return response()->json([], 204);
    }
}
