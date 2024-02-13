<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraSaleRequest;
use App\Http\Requests\UpdateExtraSaleRequest;
use App\Http\Resources\ExtraSaleResource;
use App\Models\ExtraSale;
use Illuminate\Http\Request;

class ExtraSaleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ExtraSaleController::class, 'extraSale');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = ExtraSale::query();

        if ($request->input('filter_by')) {
            $filterableArray = $request->input('filter_by');
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $extraSales = $query->paginate($perPage);

        return response()->json([
            'data' => ExtraSaleResource::collection($extraSales),
            'pagination' => [
                'current_page' => $extraSales->currentPage(),
                'total_pages' => $extraSales->lastPage(),
                'per_page' => $extraSales->perPage(),
                'total' => $extraSales->total(),
            ],
            'sortable_fields' => ExtraSale::sortableFields()
        ], 200);
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
