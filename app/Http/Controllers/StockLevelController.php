<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockLimitRequest;
use App\Http\Resources\StockLevelResource;
use App\Models\StockLevel;
use Illuminate\Http\Request;

class StockLevelController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = StockLevel::with(['product.unit', 'product.productGroup', 'stock'])
            ->where(function ($q) {
                $q->whereNotNull('min_quantity')->orWhereNotNull('max_quantity');
            });

        if ($request->input('stock_id')) {
            $query->where('stock_id', $request->input('stock_id'));
        }

        if ($request->input('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('product_name') . '%');
            });
        }

        if ($request->boolean('limit_reached')) {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('min_quantity')
                        ->whereColumn('current_quantity', '<=', 'min_quantity');
                })->orWhere(function ($sub) {
                    $sub->whereNotNull('max_quantity')
                        ->whereColumn('current_quantity', '>=', 'max_quantity');
                });
            });
        }

        $stockLevels = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'data' => StockLevelResource::collection($stockLevels),
            'pagination' => [
                'page' => $stockLevels->currentPage(),
                'total_pages' => $stockLevels->lastPage(),
                'per_page' => $stockLevels->perPage(),
                'total' => $stockLevels->total(),
            ],
        ], 200);
    }

    public function upsert(StoreStockLimitRequest $request)
    {
        $stockLevel = StockLevel::updateOrCreate(
            [
                'product_id' => $request->product_id,
                'stock_id' => $request->stock_id,
            ],
            [
                'min_quantity' => $request->min_quantity,
                'max_quantity' => $request->max_quantity,
            ]
        );

        $stockLevel->load(['product.unit', 'product.productGroup', 'stock']);

        return response()->json(new StockLevelResource($stockLevel), 200);
    }
}
