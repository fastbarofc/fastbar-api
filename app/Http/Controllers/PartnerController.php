<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Http\Resources\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(PartnerController::class, 'partner');
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Partner::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'name' || $field == 'responsible' || $field == 'email' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $partners = $query->paginate($perPage);

        return response()->json([
            'data' => PartnerResource::collection($partners),
            'pagination' => [
                'page' => $partners->currentPage(),
                'total_pages' => $partners->lastPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
            ],
            'sortable_fields' => Partner::sortableFields(),
            'filterable_fields' => Partner::filterableFields()
        ], 200);
    }

    public function store(StorePartnerRequest $request)
    {
        $partner = Partner::create($request->all());
        return response()->json(new PartnerResource($partner), 201);
    }

    public function show(Partner $partner)
    {
        return response()->json(new PartnerResource($partner), 200);
    }

    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        $partner->update($request->all());
        return response()->json(new PartnerResource($partner), 200);
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return response()->json([], 204);
    }
}
