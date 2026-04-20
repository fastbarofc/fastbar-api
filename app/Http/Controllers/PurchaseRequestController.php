<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Requests\UpdatePurchaseRequestRequest;
use App\Http\Resources\PurchaseRequestResource;
use App\Http\Resources\PurchaseRequestProductResource;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(PurchaseRequestController::class, 'purchaseRequest');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = PurchaseRequest::with(['company', 'requester', 'sector', 'purchaseRequestProducts.product']);

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

        $purchaseRequests = $query->paginate($perPage);

        return response()->json([
            'data' => PurchaseRequestResource::collection($purchaseRequests),
            'pagination' => [
                'page' => $purchaseRequests->currentPage(),
                'total_pages' => $purchaseRequests->lastPage(),
                'per_page' => $purchaseRequests->perPage(),
                'total' => $purchaseRequests->total(),
            ],
            'sortable_fields' => PurchaseRequest::sortableFields(),
            'filterable_fields' => PurchaseRequest::filterableFields()
        ], 200);
    }

    public function store(StorePurchaseRequestRequest $request)
    {
        try {
            DB::beginTransaction();

            $purchaseRequest = PurchaseRequest::create([
                'company_id' => $request->company_id,
                'requester_id' => auth()->user()->id,
                'sector_id' => $request->sector_id,
                'expected_date' => $request->expected_date,
                'status' => $request->status ?? PurchaseRequest::STATUS_NOT_CONFIRMED,
            ]);

            foreach ($request->products as $product) {
                $purchaseRequest->purchaseRequestProducts()->create([
                    'product_id' => $product['product_id'],
                    'factor' => $product['factor'] ?? null,
                    'observation' => $product['observation'] ?? null,
                    'quantity' => $product['quantity'],
                ]);
            }

            DB::commit();

            $purchaseRequest->load(['company', 'requester', 'sector', 'purchaseRequestProducts.product']);

            return response()->json(new PurchaseRequestResource($purchaseRequest), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar pedido de compra.'], 500);
        }
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['company', 'requester', 'sector', 'purchaseRequestProducts.product']);
        return response()->json(new PurchaseRequestResource($purchaseRequest), 200);
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest)
    {
        try {
            DB::beginTransaction();

            $updateData = array_filter([
                'company_id' => $request->company_id,
                'sector_id' => $request->sector_id,
                'expected_date' => $request->expected_date,
                'status' => $request->status,
            ], function ($value) {
                return $value !== null;
            });

            $purchaseRequest->update($updateData);

            if ($request->has('products')) {
                $purchaseRequest->purchaseRequestProducts()->delete();
                
                foreach ($request->products as $product) {
                    $purchaseRequest->purchaseRequestProducts()->create([
                        'product_id' => $product['product_id'],
                        'factor' => $product['factor'] ?? null,
                        'observation' => $product['observation'] ?? null,
                        'quantity' => $product['quantity'],
                    ]);
                }
            }

            DB::commit();

            $purchaseRequest->load(['company', 'requester', 'sector', 'purchaseRequestProducts.product']);

            return response()->json(new PurchaseRequestResource($purchaseRequest), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar pedido de compra.'], 500);
        }
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        try {
            DB::beginTransaction();

            $purchaseRequest->purchaseRequestProducts()->delete();
            
            $purchaseRequest->delete();

            DB::commit();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir pedido de compra.'], 500);
        }
    }

    public function products(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['purchaseRequestProducts.product.unit']);
        
        return response()->json([
            'data' => PurchaseRequestProductResource::collection($purchaseRequest->purchaseRequestProducts)
        ], 200);
    }
} 