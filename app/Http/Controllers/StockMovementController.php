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
        $query = StockLevel::with(['product.productGroup', 'product.unit', 'department'])
            ->where('current_quantity', '>', 0);

        if ($request->input('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->input('product_group_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_group_id', $request->input('product_group_id'));
            });
        }

        $stockLevels = $query->get();

        return response()->json([
            'data' => $stockLevels->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product' => $item->product,
                    'current_quantity' => $item->current_quantity,
                    'weighted_average_price' => $item->weighted_average_price,
                ];
            })
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

