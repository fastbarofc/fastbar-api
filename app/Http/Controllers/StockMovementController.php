<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\PurchaseOrderProduct;
use App\Models\StockLevel;
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
        $perPage = $request->input('per_page', 30);
        $departmentId = $request->input('department_id');
        $orderBy = $request->input('order_by', 'name');
        $direction = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = Product::with(['productGroup', 'unit'])
            ->leftJoinSub(
                \App\Models\StockLevel::select('product_id', 'current_quantity')
                    ->when($departmentId, fn($q) => $q->where('department_id', $departmentId)),
                'sl',
                'sl.product_id',
                '=',
                'products.id'
            )
            ->select('products.*', 'sl.current_quantity as stock_quantity');

        if ($request->input('product_name')) {
            $query->where('products.name', 'like', '%' . $request->input('product_name') . '%');
        }

        if ($request->input('product_group_id')) {
            $query->where('products.product_group_id', $request->input('product_group_id'));
        }

        if ($request->input('only_with_stock')) {
            $query->where('sl.current_quantity', '>', 0);
        }

        if ($orderBy === 'current_quantity') {
            $query->orderByRaw("COALESCE(sl.current_quantity, 0) $direction")->orderBy('products.name', 'asc');
        } else {
            $query->orderBy('products.name', $direction);
        }

        $products = $query->paginate($perPage);

        $productIds = $products->pluck('id')->all();
        $stockLevels = \App\Models\StockLevel::whereIn('product_id', $productIds)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->get()
            ->keyBy('product_id');

        $data = $products->map(function ($product) use ($stockLevels) {
            $stockLevel = $stockLevels->get($product->id);

            return [
                'product_id' => $product->id,
                'product' => $product,
                'current_quantity' => $stockLevel?->current_quantity ?? 0,
                'weighted_average_price' => $stockLevel?->weighted_average_price ?? 0,
            ];
        });

        return response()->json([
            'data' => $data,
            'pagination' => [
                'page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    public function getProductStockDetail(Request $request, Product $product)
    {
        $departmentId = $request->input('department_id');

        $stockLevel = StockLevel::where('product_id', $product->id)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->first();

        $purchaseHistory = PurchaseOrderProduct::with(['purchaseOrder.company'])
            ->where('product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pop) {
                return [
                    'purchase_order_id' => $pop->purchase_order_id,
                    'company' => $pop->purchaseOrder->company->trade_name ?? $pop->purchaseOrder->company->legal_name ?? '—',
                    'quantity' => $pop->quantity,
                    'price' => $pop->price,
                    'total' => $pop->quantity * $pop->price,
                    'date' => $pop->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $lastPurchaseMovement = StockMovement::with('movementable')
            ->where('product_id', $product->id)
            ->where('type', StockMovement::TYPE_IN)
            ->where('movementable_type', \App\Models\PurchaseOrder::class)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->orderBy('created_at', 'desc')
            ->first();

        $lastPurchase = null;
        if ($lastPurchaseMovement && $lastPurchaseMovement->movementable) {
            $po = $lastPurchaseMovement->movementable->load('company');
            $lastPurchase = [
                'purchase_order_id' => $po->id,
                'company' => $po->company?->trade_name ?? $po->company?->legal_name ?? '—',
                'quantity' => $lastPurchaseMovement->quantity,
                'unit_price' => $lastPurchaseMovement->price,
                'total_value' => $lastPurchaseMovement->quantity * $lastPurchaseMovement->price,
                'date' => $lastPurchaseMovement->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $perPage = $request->input('per_page', 20);
        $movementsQuery = StockMovement::with(['department', 'movementable'])
            ->where('product_id', $product->id)
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->orderBy('created_at', 'desc');

        $movements = $movementsQuery->paginate($perPage);

        $movementData = $movements->map(function ($movement) {
            $sourceType = match (class_basename($movement->movementable_type)) {
                'PurchaseOrder' => 'Ordem de Compra',
                'Requisition' => 'Requisição',
                'Devolution' => 'Devolução',
                default => $movement->movementable_type,
            };

            $audit = $movement->audits()->where('event', 'created')->first();
            $userName = $audit && $audit->user ? $audit->user->name : null;

            return [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'price' => $movement->price,
                'department' => $movement->department->name ?? '—',
                'source_type' => $sourceType,
                'source_id' => $movement->movementable_id,
                'user' => $userName,
                'date' => $movement->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $product->load(['productGroup', 'unit']);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit->description ?? '—',
                'group' => $product->productGroup->description ?? '—',
            ],
            'stock_level' => $stockLevel ? [
                'current_quantity' => $stockLevel->current_quantity,
                'weighted_average_price' => $stockLevel->weighted_average_price,
                'total_value' => $stockLevel->total_value,
            ] : null,
            'last_purchase' => $lastPurchase,
            'purchase_history' => $purchaseHistory,
            'movements' => [
                'data' => $movementData,
                'pagination' => [
                    'page' => $movements->currentPage(),
                    'total_pages' => $movements->lastPage(),
                    'per_page' => $movements->perPage(),
                    'total' => $movements->total(),
                ],
            ],
        ], 200);
    }
}

