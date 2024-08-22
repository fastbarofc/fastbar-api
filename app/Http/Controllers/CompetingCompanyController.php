<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetingCompanyRequest;
use App\Http\Requests\UpdateCompetingCompanyRequest;
use App\Http\Resources\CompetingCompanyResource;
use App\Models\CompetingCompany;
use Illuminate\Http\Request;

class CompetingCompanyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CompetingCompanyController::class, 'competing_company');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = CompetingCompany::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $query->filterByField(
                        field: $field,
                        value: $value,
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $competingCompany = $query->paginate($perPage);

        return response()->json([
            'data' => CompetingCompanyResource::collection($competingCompany),
            'pagination' => [
                'current_page' => $competingCompany->currentPage(),
                'total_pages' => $competingCompany->lastPage(),
                'per_page' => $competingCompany->perPage(),
                'total' => $competingCompany->total(),
            ],
            'sortable_fields' => CompetingCompany::sortableFields(),
            'filterable_fields' => CompetingCompany::filterableFields()
        ], 200);
    }

    public function store(StoreCompetingCompanyRequest $request)
    {
        $competingCompany = CompetingCompany::create($request->all());
        return response()->json(new CompetingCompanyResource($competingCompany), 201);
    }

    public function show(CompetingCompany $competingCompany)
    {
        return response()->json(new CompetingCompanyResource($competingCompany), 200);
    }

    public function update(UpdateCompetingCompanyRequest $request, CompetingCompany $competingCompany)
    {
        $competingCompany->update($request->all());
        return response()->json(new CompetingCompanyResource($competingCompany), 200);
    }

    public function destroy(CompetingCompany $competingCompany)
    {
        $competingCompany->delete();
        return response()->json([], 204);
    }
}
