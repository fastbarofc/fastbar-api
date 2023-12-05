<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $partners = Partner::paginate($perPage);

        return response()->json([
            'data' => PartnerResource::collection($partners),
            'pagination' => [
                'current_page' => $partners->currentPage(),
                'total_pages' => $partners->lastPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
            ],
        ], 200);
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
