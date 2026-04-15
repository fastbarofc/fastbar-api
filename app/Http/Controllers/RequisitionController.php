<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequisitionRequest;
use App\Http\Resources\RequisitionResource;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequisitionController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = Requisition::with(['event', 'stock', 'requester', 'requisitionItems.product', 'devolutions.devolutionItems']);

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

        $requisitions = $query->paginate($perPage);

        return response()->json([
            'data' => RequisitionResource::collection($requisitions),
            'pagination' => [
                'page' => $requisitions->currentPage(),
                'total_pages' => $requisitions->lastPage(),
                'per_page' => $requisitions->perPage(),
                'total' => $requisitions->total(),
            ],
            'sortable_fields' => Requisition::sortableFields(),
            'filterable_fields' => Requisition::filterableFields()
        ], 200);
    }

    public function store(StoreRequisitionRequest $request)
    {
        try {
            DB::beginTransaction();

            $requisition = Requisition::create([
                'event_id'                  => $request->event_id,
                'stock_id'             => $request->stock_id,
                'destination_stock_id' => $request->destination_stock_id,
                'requester_id'              => auth()->user()->id,
                'notes'                     => $request->notes,
                'status'                    => Requisition::STATUS_OPEN,
            ]);

            foreach ($request->items as $item) {
                RequisitionItem::create([
                    'requisition_id' => $requisition->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $requisition->load('requisitionItems.product');

            $this->stockService->executeRequisition($requisition);

            DB::commit();

            $requisition->load(['event', 'stock', 'requester', 'requisitionItems.product', 'devolutions.devolutionItems']);

            return response()->json(new RequisitionResource($requisition), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar requisição.'], 500);
        }
    }

    public function show(Requisition $requisition)
    {
        $requisition->load(['event', 'stock', 'requester', 'requisitionItems.product', 'devolutions.devolutionItems']);
        return response()->json(new RequisitionResource($requisition), 200);
    }

    public function destroy(Requisition $requisition)
    {
        if (in_array($requisition->status, [Requisition::STATUS_PARTIAL, Requisition::STATUS_RETURNED])) {
            return response()->json(['error' => 'Não é possível excluir uma requisição que já foi devolvida ou parcialmente devolvida.'], 422);
        }

        try {
            DB::beginTransaction();

            $requisition->requisitionItems()->delete();
            $requisition->delete();

            DB::commit();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir requisição.'], 500);
        }
    }

    public function byEvent(Request $request, $eventId)
    {
        $requisitions = Requisition::with(['stock', 'requester', 'requisitionItems.product', 'devolutions.devolutionItems'])
            ->where('event_id', $eventId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => RequisitionResource::collection($requisitions),
        ], 200);
    }
}
