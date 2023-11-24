<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraSaleRequest;
use App\Http\Requests\UpdateExtraSaleRequest;
use App\Http\Resources\ExtraSaleResource;
use App\Models\ExtraSale;

class ExtraSaleController extends Controller
{
    public function index()
    {
        $extraSales = ExtraSale::all();
        return response()->json(ExtraSaleResource::collection($extraSales), 200);
    }

    public function store(StoreExtraSaleRequest $request)
    {
        $extraSale = ExtraSale::create($request->all());
        return response()->json(new ExtraSaleResource($extraSale), 201);
    }

    public function show(ExtraSale $extraSale)
    {
        return response()->json(new ExtraSaleResource($extraSale), 200);
    }

    public function update(UpdateExtraSaleRequest $request, ExtraSale $extraSale)
    {
        $extraSale->update($request->all());
        return response()->json(new ExtraSaleResource($extraSale), 200);
    }

    public function destroy(ExtraSale $extraSale)
    {
        $extraSale->delete();
        return response()->json([], 204);
    }
}
