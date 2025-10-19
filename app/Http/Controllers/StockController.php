<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Models\Stock;
use App\Http\Resources\StockResource;
use Illuminate\Http\Request;

class StockController extends Controller
{
    
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(MaterialController::class, 'material');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Stock::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField($field, $value);
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $stocks = $query->paginate($perPage);

        return response()->json([
            'data' => StockResource::collection($stocks),
            'pagination' => [
                'page' => $stocks->currentPage(),
                'total_pages' => $stocks->lastPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
            ],
            'sortable_fields' => Stock::sortableFields(),
            'filterable_fields' => Stock::filterableFields()
        ], 200);
    }

    public function store(StoreStockRequest $request)
    {
        $stock = Stock::create($request->all());
        return response()->json(new StockResource($stock), 201);
    }
    
    public function show(Stock $stock)
    {
        return response()->json(new StockResource($stock), 200);
    }
    
    public function update(UpdateStockRequest $request, Stock $stock)
    {
        $stock->update($request->all());
        return response()->json(new StockResource($stock), 200);
    }
    
    public function destroy(Stock $stock)
    {
        $stock->delete();
        return response()->json(null, 204);
    }
}
