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

                    if ($field === 'search') {
                        if (!empty($value)) {
                            $query->where(function ($subQuery) use ($value) {
                                foreach (FinancialOperation::searchableFields() as $searchableField) {
                                    $subQuery->orWhere('financial_operations.' . $searchableField, 'like', "%$value%");
                                }
                                if (ctype_digit(trim((string) $value))) {
                                    $subQuery->orWhere('financial_operations.id', (int) trim($value));
                                }
                            });
                        }
                    } else {
                        $operator = $field == 'description' ? 'like' : '=';
                        $query->where($field, $operator, $operator == 'like' ? "%$value%" : $value);
                    }
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

    /**
     * Salvar a estrutura do DRE/ARM (Configurações > Plano de contas > Estrutura do DRE):
     * grupo e posição de cada conta. "fora" = não entra no demonstrativo.
     */
    public function structure(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:financial_operations,id',
            'items.*.dre_section' => 'nullable|in:receita_total,deducoes,custos_variaveis,despesas_operacionais,despesas_nao_operacionais,receitas_nao_operacionais,retirada_socios,fora',
            'items.*.dre_order' => 'nullable|integer',
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                FinancialOperation::where('id', $item['id'])->update([
                    'dre_section' => $item['dre_section'] ?? 'fora',
                    'dre_order' => $item['dre_order'] ?? null,
                ]);
            }
        });
        return response()->json(['saved' => count($data['items'])]);
    }

    /** Carregar o plano padrão de buffet: grava só as contas (códigos) que ainda não existem. */
    public function defaultPlan()
    {
        $existing = FinancialOperation::query()->pluck('code')->flip();
        $created = 0;
        $order = [];
        foreach (FinancialOperation::DEFAULT_PLAN as [$code, $description, $nature, $type, $section]) {
            $order[$section] = ($order[$section] ?? 0) + 1;
            if (isset($existing[$code])) {
                continue;
            }
            FinancialOperation::create([
                'code' => $code,
                'description' => $description,
                'account' => 'analytic',
                'nature' => $nature,
                'type' => $type,
                'dre_section' => $section,
                'dre_order' => 1000 + $order[$section],
                'active' => true,
            ]);
            $created++;
        }
        return response()->json(['created' => $created]);
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