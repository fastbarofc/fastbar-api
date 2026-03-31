<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDevolutionRequest;
use App\Http\Resources\DevolutionResource;
use App\Models\Devolution;
use App\Models\DevolutionItem;
use App\Models\Requisition;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevolutionController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = Devolution::with([
            'requisition.event',
            'requisition.requisitionItems.product',
            'department',
            'registeredBy',
            'devolutionItems.product',
        ]);

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

        $devolutions = $query->paginate($perPage);

        return response()->json([
            'data' => DevolutionResource::collection($devolutions),
            'pagination' => [
                'page' => $devolutions->currentPage(),
                'total_pages' => $devolutions->lastPage(),
                'per_page' => $devolutions->perPage(),
                'total' => $devolutions->total(),
            ],
            'sortable_fields' => Devolution::sortableFields(),
            'filterable_fields' => Devolution::filterableFields()
        ], 200);
    }

    public function store(StoreDevolutionRequest $request)
    {
        try {
            DB::beginTransaction();

            $requisition = Requisition::with('requisitionItems')->findOrFail($request->requisition_id);

            if ($requisition->status === Requisition::STATUS_RETURNED) {
                return response()->json(['error' => 'Esta requisição já foi completamente devolvida.'], 422);
            }

            $devolution = Devolution::create([
                'requisition_id' => $request->requisition_id,
                'department_id' => $requisition->department_id,
                'registered_by_id' => auth()->user()->id,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                DevolutionItem::create([
                    'devolution_id' => $devolution->id,
                    'product_id' => $item['product_id'],
                    'quantity_returned' => $item['quantity_returned'],
                ]);
            }

            $devolution->load('devolutionItems.product', 'requisition');

            $this->stockService->registerDevolutionMovements($devolution);

            DB::commit();

            $devolution->load([
                'requisition.event',
                'department',
                'registeredBy',
                'devolutionItems.product',
            ]);

            return response()->json(new DevolutionResource($devolution), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao registrar devolução.'], 500);
        }
    }

    public function show(Devolution $devolution)
    {
        $devolution->load([
            'requisition.event',
            'requisition.requisitionItems.product',
            'department',
            'registeredBy',
            'devolutionItems.product',
        ]);
        return response()->json(new DevolutionResource($devolution), 200);
    }

    public function showByRequisition(Requisition $requisition)
    {
        $devolution = Devolution::with([
            'requisition.event',
            'department',
            'registeredBy',
            'devolutionItems.product',
        ])->where('requisition_id', $requisition->id)->first();

        if (!$devolution) {
            return response()->json(['error' => 'Devolução não encontrada para esta requisição.'], 404);
        }

        return response()->json(new DevolutionResource($devolution), 200);
    }
}
