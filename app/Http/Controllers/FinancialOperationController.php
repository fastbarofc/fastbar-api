<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialOperationRequest;
use App\Http\Requests\UpdateFinancialOperationRequest;
use App\Http\Resources\FinancialOperationResource;
use App\Models\FinancialOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinancialOperationController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(FinancialOperation::class, 'financialOperation');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = FinancialOperation::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'description' ? 'like' : '=';
                    $query->where($field, $operator, $operator == 'like' ? "%$value%" : $value);
                }
            }
        }

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortableField, $direction);
        }

        $financialOperations = $query->paginate($perPage);

        return response()->json([
            'data' => FinancialOperationResource::collection($financialOperations),
            'pagination' => [
                'page' => $financialOperations->currentPage(),
                'total_pages' => $financialOperations->lastPage(),
                'per_page' => $financialOperations->perPage(),
                'total' => $financialOperations->total(),
            ],
            'sortable_fields' => ['code', 'description', 'account', 'nature', 'type'],
            'filterable_fields' => ['code', 'description', 'account', 'nature', 'type'],
        ], 200);
    }

    public function store(StoreFinancialOperationRequest $request)
    {
        $financialOperation = FinancialOperation::create($request->all());
        return response()->json(new FinancialOperationResource($financialOperation), 201);
    }

    public function show(FinancialOperation $financialOperation)
    {
        return response()->json(new FinancialOperationResource($financialOperation), 200);
    }

    public function update(UpdateFinancialOperationRequest $request, FinancialOperation $financialOperation)
    {
        $financialOperation->update($request->all());
        return response()->json(new FinancialOperationResource($financialOperation), 200);
    }

    public function destroy(FinancialOperation $financialOperation)
    {
        $financialOperation->delete();
        return response()->json([], 204);
    }
}