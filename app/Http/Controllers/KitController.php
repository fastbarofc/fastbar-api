<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\UpdateKitRequest;
use App\Http\Resources\KitResource;
use App\Models\Kit;

class KitController extends Controller
{
    public function index()
    {
        $kits = Kit::all();
        return response ()->json(KitResource::collection($kits), 200);
    }

    public function store(StoreKitRequest $request)
    {
        $kit = Kit::create($request->all());
        return response()->json(new KitResource($kit), 201);
    }

    public function show(Kit $kit)
    {
        return response()->json(new KitResource($kit), 200);
    }

    public function update(UpdateKitRequest $request, Kit $kit)
    {
        $kit->update($request->all());
        return response()->json(new KitResource($kit), 200);
    }

    public function destroy(Kit $kit)
    {
        $kit->delete();
        return response()->json([], 204);
    }
}
