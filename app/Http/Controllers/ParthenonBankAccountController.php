<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParthenonBankAccountRequest;
use App\Http\Requests\UpdateParthenonBankAccountRequest;
use App\Http\Resources\ParthenonBankAccountResource;
use App\Models\ParthenonBankAccount;
use Illuminate\Http\Request;

class ParthenonBankAccountController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(ParthenonBankAccount::class, 'parthernon_bank_account');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = ParthenonBankAccount::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'bank_name' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
        }

        $accounts = $query->paginate($perPage);

        return response()->json([
            'data' => ParthenonBankAccountResource::collection($accounts),
            'pagination' => [
                'page' => $accounts->currentPage(),
                'total_pages' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
            'sortable_fields' => ParthenonBankAccount::sortableFields(),
            'filterable_fields' => ParthenonBankAccount::filterableFields()
        ], 200);
    }

    public function store(StoreParthenonBankAccountRequest $request)
    {
        $account = ParthenonBankAccount::create($request->all());
        return response()->json(new ParthenonBankAccountResource($account), 201);
    }

    public function show(ParthenonBankAccount $parthenonBankAccount)
    {
        return response()->json(new ParthenonBankAccountResource($parthenonBankAccount), 200);
    }

    public function update(UpdateParthenonBankAccountRequest $request, ParthenonBankAccount $parthenonBankAccount)
    {
        $parthenonBankAccount->update($request->all());
        return response()->json(new ParthenonBankAccountResource($parthenonBankAccount), 200);
    }

    public function destroy(ParthenonBankAccount $parthenonBankAccount)
    {
        $parthenonBankAccount->delete();
        return response()->json([], 204);
    }
}

