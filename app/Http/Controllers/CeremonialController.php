<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCeremonialRequest;
use App\Http\Requests\UpdateCeremonialRequest;
use App\Http\Resources\CeremonialResource;
use App\Models\Ceremonial;
use Illuminate\Http\Request;

class CeremonialController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Ceremonial::class, 'ceremonial');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Ceremonial::query();

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

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $ceremonials = Ceremonial::paginate($perPage);

        return response()->json([
            'data'       => CeremonialResource::collection($ceremonials),
            'pagination' => [
                'page' => $ceremonials->currentPage(),
                'total_pages'  => $ceremonials->lastPage(),
                'per_page'     => $ceremonials->perPage(),
                'total'        => $ceremonials->total(),
            ],
            'sortable_fields' => Ceremonial::sortableFields(),
            'filterable_fields' => Ceremonial::filterableFields()
        ], 200);
    }

    public function store(StoreCeremonialRequest $request)
    {
        $ceremonial = Ceremonial::create($request->all());
        return response()->json(new CeremonialResource($ceremonial), 201);
    }

    public function show(Ceremonial $ceremonial)
    {
        return response()->json(new CeremonialResource($ceremonial), 200);
    }

    public function update(UpdateCeremonialRequest $request, Ceremonial $ceremonial)
    {
        $ceremonial->update($request->all());
        return response()->json(new CeremonialResource($ceremonial), 200);
    }

    public function destroy(Ceremonial $ceremonial)
    {
        $ceremonial->delete();
        return response()->json([], 204);
    }
}
