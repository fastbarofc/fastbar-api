<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(Company::class, 'company');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Company::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'internal_name' || $field == 'legal_name' || $field == 'trade_name' ? 'like' : '=';
                    $query->where($field, $operator, $operator == 'like' ? "%$value%" : $value);
                }
            }
        }

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderBy($sortableField, $direction);
        }

        $companies = $query->paginate($perPage);

        return response()->json([
            'data' => CompanyResource::collection($companies),
            'pagination' => [
                'page' => $companies->currentPage(),
                'total_pages' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
            'sortable_fields' => ['internal_name', 'legal_name', 'trade_name', 'type'],
            'filterable_fields' => ['internal_name', 'legal_name', 'trade_name', 'type'],
        ], 200);
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->all());
        return response()->json(new CompanyResource($company), 201);
    }

    public function show(Company $company)
    {
        return response()->json(new CompanyResource($company->loadMissing('city')), 200);
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->all());
        return response()->json(new CompanyResource($company->loadMissing('city')), 200);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json([], 204);
    }
}