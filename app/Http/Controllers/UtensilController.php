<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUtensilRequest;
use App\Http\Requests\UpdateUtensilRequest;
use App\Models\Utensil;
use Illuminate\Http\Request;
use App\Http\Resources\UtensilResource;

class UtensilController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(UtensilController::class, 'utensil');
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Utensil::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $utensils = $query->paginate($perPage);

        return response()->json([
            'data' => UtensilResource::collection($utensils),
            'pagination' => [
                'current_page' => $utensils->currentPage(),
                'total_pages' => $utensils->lastPage(),
                'per_page' => $utensils->perPage(),
                'total' => $utensils->total(),
            ],
            'sortable_fields' => Utensil::sortableFields(),
            'filterable_fields' => Utensil::filterableFields()
        ], 200);
    }
    
    public function store(StoreUtensilRequest $request)
    {
        $utensils = Utensil::create($request->all());
        return response()->json(new UtensilResource($utensils), 201);
    }
    
    public function show(Utensil $utensil)
    {
        return response()->json(new UtensilResource($utensil), 200);
    }
    
    public function update(UpdateUtensilRequest $request, Utensil $utensil)
    {
        $utensil->update($request->all());
        return response()->json(new UtensilResource($utensil), 200);
    }
    
    public function destroy(Utensil $utensil)
    {
        $utensil->delete();
        return response()->json(null, 204);
    }
}
