<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestProduct;
use App\Services\PurchaseRequestService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $query = PurchaseOrder::with(['company', 'financialOperation', 'costCenter', 'stock', 'products', 'creator', 'lastEditor']);

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
        $this->ensureItemsAreAvailable($request->products);

        try {
            DB::beginTransaction();

            $userId = auth()->id();

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $request->company_id,
                'price' => $request->price,
                'financial_operation_id' => $request->financial_operation_id,
                'cost_center_id' => $request->cost_center_id,
                'stock_id' => $request->stock_id,
                'status' => PurchaseOrder::STATUS_PENDING,
                'created_by' => $userId,
                'updated_by' => $userId,
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

            $this->markRequestItemsAsOrdered($request->products, true);

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

            $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'products', 'creator', 'lastEditor']);

            return response()->json(new PurchaseOrderResource($purchaseOrder), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar ordem de compra.'], 500);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'purchaseOrderProducts.product', 'creator', 'lastEditor']);
        return response()->json(new PurchaseOrderResource($purchaseOrder), 200);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_PENDING) {
            return response()->json(['error' => 'Apenas ordens de compra com status pendente podem ser editadas.'], 422);
        }

        $oldItems = $purchaseOrder->purchaseOrderProducts()
            ->get(['purchase_request_id', 'product_id'])
            ->map(fn ($item) => $item->purchase_request_id . ':' . $item->product_id)
            ->all();

        if ($request->has('products')) {
            $newItemKeys = collect($request->products)
                ->map(fn ($p) => $p['purchase_request_id'] . ':' . $p['product_id'])
                ->all();

            $itemsToCheck = collect($request->products)->filter(
                fn ($p) => !in_array($p['purchase_request_id'] . ':' . $p['product_id'], $oldItems)
            )->values()->all();

            $this->ensureItemsAreAvailable($itemsToCheck);
        }

        try {
            DB::beginTransaction();

            $oldPurchaseRequestIds = $purchaseOrder->purchaseOrderProducts()
                ->pluck('purchase_request_id')
                ->unique()
                ->filter();

            $oldItemPairs = $purchaseOrder->purchaseOrderProducts()
                ->get(['purchase_request_id', 'product_id'])
                ->map(fn ($item) => ['purchase_request_id' => $item->purchase_request_id, 'product_id' => $item->product_id])
                ->all();

            $updateData = array_filter([
                'company_id' => $request->company_id,
                'price' => $request->price,
                'financial_operation_id' => $request->financial_operation_id,
                'cost_center_id' => $request->cost_center_id,
                'stock_id' => $request->stock_id,
            ], function ($value) {
                return $value !== null;
            });

            $updateData['updated_by'] = auth()->id();

            $purchaseOrder->update($updateData);

            $newPurchaseRequestIds = collect();
            $newItemPairs = [];

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
                    $newItemPairs[] = [
                        'purchase_request_id' => $product['purchase_request_id'],
                        'product_id' => $product['product_id'],
                    ];
                }
                $purchaseOrder->products()->sync($productsToSync);

                $this->syncOrderedFlags($oldItemPairs, $newItemPairs);

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

            $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'products', 'creator', 'lastEditor']);

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
                $items = $purchaseOrder->purchaseOrderProducts()
                    ->get(['purchase_request_id', 'product_id']);

                $purchaseRequestIds = $items->pluck('purchase_request_id')->unique()->filter();

                $itemPairs = $items->map(fn ($i) => [
                    'purchase_request_id' => $i->purchase_request_id,
                    'product_id' => $i->product_id,
                ])->all();

                $this->markRequestItemsAsOrdered($itemPairs, false);
            }

            $oldStatus = $purchaseOrder->status;
            $purchaseOrder->update(['status' => $newStatus, 'updated_by' => auth()->id()]);

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

            $purchaseOrder->load(['company', 'financialOperation', 'costCenter', 'stock', 'products', 'creator', 'lastEditor']);

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

            $items = $purchaseOrder->purchaseOrderProducts()
                ->get(['purchase_request_id', 'product_id']);

            $purchaseRequestIds = $items->pluck('purchase_request_id')->unique()->filter();

            $itemPairs = $items->map(fn ($i) => [
                'purchase_request_id' => $i->purchase_request_id,
                'product_id' => $i->product_id,
            ])->all();

            if ($purchaseOrder->status !== PurchaseOrder::STATUS_CANCELLED) {
                $this->markRequestItemsAsOrdered($itemPairs, false);
            }

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

    private function ensureItemsAreAvailable(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $errors = [];

        foreach ($items as $index => $item) {
            $exists = PurchaseRequestProduct::where('purchase_request_id', $item['purchase_request_id'])
                ->where('product_id', $item['product_id'])
                ->where('ordered', false)
                ->exists();

            if (!$exists) {
                $errors["products.$index.product_id"] = ['O item selecionado já está incluído em outra ordem de compra.'];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function markRequestItemsAsOrdered(array $items, bool $ordered): void
    {
        foreach ($items as $item) {
            PurchaseRequestProduct::where('purchase_request_id', $item['purchase_request_id'])
                ->where('product_id', $item['product_id'])
                ->update(['ordered' => $ordered]);
        }
    }

    private function syncOrderedFlags(array $oldItems, array $newItems): void
    {
        $oldKeys = array_map(fn ($i) => $i['purchase_request_id'] . ':' . $i['product_id'], $oldItems);
        $newKeys = array_map(fn ($i) => $i['purchase_request_id'] . ':' . $i['product_id'], $newItems);

        $removed = array_filter($oldItems, fn ($i) => !in_array($i['purchase_request_id'] . ':' . $i['product_id'], $newKeys));
        $added = array_filter($newItems, fn ($i) => !in_array($i['purchase_request_id'] . ':' . $i['product_id'], $oldKeys));

        $this->markRequestItemsAsOrdered($removed, false);
        $this->markRequestItemsAsOrdered($added, true);
    }
}
