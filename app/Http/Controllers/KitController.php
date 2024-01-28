<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\UpdateKitRequest;
use App\Http\Resources\KitResource;
use App\Models\Kit;
use Illuminate\Http\Request;

class KitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(KitController::class, 'kit');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Kit::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $kits = $query->paginate($perPage);

        return response()->json([
            'data' => KitResource::collection($kits),
            'pagination' => [
                'current_page' => $kits->currentPage(),
                'total_pages' => $kits->lastPage(),
                'per_page' => $kits->perPage(),
                'total' => $kits->total(),
            ],
            'soretable_fields' => Kit::sortableFields()
        ], 200);
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
