<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected $stockService;
    protected $purchaseRequestService;

    public function __construct(StockService $stockService, PurchaseRequestService $purchaseRequestService)
    {
        $this->stockService = $stockService;
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = PurchaseOrder::with(['company', 'financialOperation', 'costCenter', 'stock', 'products']);

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

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
        }

        $purchaseOrders = $query->paginate($perPage);

        return response()->json([
            'data' => PurchaseOrderResource::collection($purchaseOrders),
            'pagination' => [
                'page' => $purchaseOrders->currentPage(),
                'total_pages' => $purchaseOrders->lastPage(),
                'per_page' => $purchaseOrders->perPage(),
                'total' => $purchaseOrders->total(),
            ],
            'sortable_fields' => PurchaseOrder::sortableFields(),
            'filterable_fields' => PurchaseOrder::filterableFields()
        ], 200);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $request->company_id,
                'price' => $request->price,
                'financial_operation_id' => $request->financial_operation_id,
                'cost_center_id' => $request->cost_center_id,
                'stock_id' => $request->stock_id,
                'status' => PurchaseOrder::STATUS_PENDING,
            ]);

            $productsToSync = [];
            foreach ($request->products as $product) {
                $productsToSync[$product['product_id']] = [
                    'purchase_request_id' => $product['purchase_request_id'],
                    'factor' => $product['factor'] ?? null,
                    'observation' => $product['observation'] ?? null,
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                ];
            }
            $purchaseOrder->products()->sync($productsToSync);

            $this->stockService->registerPurchaseOrderMovements($purchaseOrder);

            $purchaseRequestIds = collect($request->products)
                ->pluck('purchase_request_id')
                ->unique()
                ->filter();

            foreach ($purchaseRequestIds as $purchaseRequestId) {
                $purchaseRequest = PurchaseRequest::find($purchaseRequestId);
                if ($purchaseRequest) {
                    $this->purchaseRequestService->syncFulfillmentStatus($purchaseRequest);
                }
            }

            DB::commit();

            $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'products']);

            return response()->json(new PurchaseOrderResource($purchaseOrder), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar ordem de compra.'], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'purchaseOrderProducts.product']);
        return response()->json(new PurchaseOrderResource($purchaseOrder), 200);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_PENDING) {
            return response()->json(['error' => 'Apenas ordens de compra com status pendente podem ser editadas.'], 422);
        }

        try {
            DB::beginTransaction();

            $oldPurchaseRequestIds = $purchaseOrder->purchaseOrderProducts()
                ->pluck('purchase_request_id')
                ->unique()
                ->filter();

            $updateData = array_filter([
                'company_id' => $request->company_id,
                'price' => $request->price,
                'financial_operation_id' => $request->financial_operation_id,
                'cost_center_id' => $request->cost_center_id,
                'stock_id' => $request->stock_id,
            ], function ($value) {
                return $value !== null;
            });

            $purchaseOrder->update($updateData);

            $newPurchaseRequestIds = collect();

            if ($request->has('products')) {
                $productsToSync = [];
                foreach ($request->products as $product) {
                    $productsToSync[$product['product_id']] = [
                        'purchase_request_id' => $product['purchase_request_id'],
                        'factor' => $product['factor'] ?? null,
                        'observation' => $product['observation'] ?? null,
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                    ];
                }
                $purchaseOrder->products()->sync($productsToSync);

                $newPurchaseRequestIds = collect($request->products)
                    ->pluck('purchase_request_id')
                    ->unique()
                    ->filter();
            }

            $allPurchaseRequestIds = $oldPurchaseRequestIds->merge($newPurchaseRequestIds)->unique();

            foreach ($allPurchaseRequestIds as $purchaseRequestId) {
                $purchaseRequest = PurchaseRequest::find($purchaseRequestId);
                if ($purchaseRequest) {
                    $this->purchaseRequestService->syncFulfillmentStatus($purchaseRequest);
                }
            }

            DB::commit();

            $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'products']);

            return response()->json(new PurchaseOrderResource($purchaseOrder), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar ordem de compra.'], 500);
        }
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'status' => 'required|string|in:received,invoice_received,paid,cancelled',
        ]);

        $newStatus = $request->status;
        $allowedTransitions = PurchaseOrder::ALLOWED_TRANSITIONS[$purchaseOrder->status] ?? [];

        if (!in_array($newStatus, $allowedTransitions)) {
            return response()->json([
                'error' => 'Transição de status inválida.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $purchaseRequestIds = collect();
            if ($newStatus === PurchaseOrder::STATUS_CANCELLED) {
                $purchaseRequestIds = $purchaseOrder->purchaseOrderProducts()
                    ->pluck('purchase_request_id')
                    ->unique()
                    ->filter();
            }

            $oldStatus = $purchaseOrder->status;
            $purchaseOrder->update(['status' => $newStatus]);

            if ($this->stockService->shouldRegisterMovements($purchaseOrder, $oldStatus)) {
                $this->stockService->registerPurchaseOrderMovements($purchaseOrder);
            }

            foreach ($purchaseRequestIds as $purchaseRequestId) {
                $purchaseRequest = PurchaseRequest::find($purchaseRequestId);
                if ($purchaseRequest) {
                    $this->purchaseRequestService->syncFulfillmentStatus($purchaseRequest);
                }
            }

            DB::commit();

            $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'products']);

            return response()->json(new PurchaseOrderResource($purchaseOrder), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar status da ordem de compra.'], 500);
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        try {
            DB::beginTransaction();

            $purchaseRequestIds = $purchaseOrder->purchaseOrderProducts()
                ->pluck('purchase_request_id')
                ->unique()
                ->filter();

            $purchaseOrder->products()->detach();
            $purchaseOrder->delete();

            foreach ($purchaseRequestIds as $purchaseRequestId) {
                $purchaseRequest = PurchaseRequest::find($purchaseRequestId);
                if ($purchaseRequest) {
                    $this->purchaseRequestService->syncFulfillmentStatus($purchaseRequest);
                }
            }

            DB::commit();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir ordem de compra.'], 500);
        }
    }
}
