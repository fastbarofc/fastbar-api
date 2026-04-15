<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Http\Resources\StockResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(Stock::class, 'stock');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

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

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
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
        try {
            DB::beginTransaction();

            $stock = Stock::create([
                'name' => $request->name,
            ]);

            DB::commit();

            return response()->json(new StockResource($stock), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar estoque.'], 500);
        }
    }

    public function show(Stock $stock)
    {
        return response()->json(new StockResource($stock), 200);
    }

    public function update(UpdateStockRequest $request, Stock $stock)
    {
        try {
            DB::beginTransaction();

            $updateData = array_filter([
                'name' => $request->name,
            ], function ($value) {
                return $value !== null;
            });

            $stock->update($updateData);

            DB::commit();

            return response()->json(new StockResource($stock), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar estoque.'], 500);
        }
    }

    public function destroy(Stock $stock)
    {
        try {
            DB::beginTransaction();

            $stock->delete();

            DB::commit();

            return response()->json(['message' => 'Estoque excluído com sucesso.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir estoque.'], 500);
        }
    }
}
