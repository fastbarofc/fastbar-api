<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\UpdateKitRequest;
use App\Http\Resources\KitResource;
use App\Http\Resources\KitTreeResource;
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

        $kits = $query->paginate($perPage);

        return response()->json([
            'data' => KitTreeResource::collection($kits),
            'pagination' => [
                'current_page' => $kits->currentPage(),
                'total_pages' => $kits->lastPage(),
                'per_page' => $kits->perPage(),
                'total' => $kits->total(),
            ],
            'sortable_fields' => Kit::sortableFields(),
            'filterable_fields' => Kit::filterableFields()
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
