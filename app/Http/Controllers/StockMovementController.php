<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = StockMovement::with(['product', 'department', 'movementable']);

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

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
        }

        $stockMovements = $query->paginate($perPage);

        return response()->json([
            'data' => StockMovementResource::collection($stockMovements),
            'pagination' => [
                'page' => $stockMovements->currentPage(),
                'total_pages' => $stockMovements->lastPage(),
                'per_page' => $stockMovements->perPage(),
                'total' => $stockMovements->total(),
            ],
            'sortable_fields' => StockMovement::sortableFields(),
            'filterable_fields' => StockMovement::filterableFields()
        ], 200);
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load(['product', 'department', 'movementable']);
        return response()->json(new StockMovementResource($stockMovement), 200);
    }

    public function getStock(Request $request)
    {
        $query = StockMovement::with(['product.productGroup', 'department']);

        if ($request->input('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->input('purchase_order_id')) {
            $query->where('movementable_type', 'App\Models\PurchaseOrder')
                  ->where('movementable_id', $request->input('purchase_order_id'));
        }

        if ($request->input('product_group_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('product_group_id', $request->input('product_group_id'));
            });
        }

        $stockByProduct = $query
            ->selectRaw('product_id, 
                SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as current_quantity, 
                AVG(price) as average_price')
            ->groupBy('product_id')
            ->havingRaw('current_quantity > 0')
            ->get();

        $stockByProduct->load('product.productGroup', 'product.unit');

        return response()->json([
            'data' => $stockByProduct->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'current_quantity' => $item->current_quantity,
                    'average_price' => $item->average_price,
                ];
            })
        ], 200);
    }
}

