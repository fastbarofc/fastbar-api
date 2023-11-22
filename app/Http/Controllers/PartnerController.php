<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;

class PartnerController extends Controller
{
    public function index()
    {
        $partners = Partner::all();
        return response()->json(PartnerResource::collection($partners), 200);
    }

    public function store(StorePartnerRequest $request)
    {
        $partner = Partner::create($request->all());
        return response()->json(new PartnerResource($partner), 201);
    }

    public function show(Partner $partner)
    {
        return response()->json(new PartnerResource($partner), 200);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        $partner->update($request->all());
        return response()->json(new PartnerResource($partner), 200);
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return response()->json([], 204);
    }
}
