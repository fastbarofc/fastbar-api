<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFailureRequest;
use App\Http\Requests\UpdateFailureRequest;
use App\Http\Resources\FailureResource;
use App\Models\Failure;
use Illuminate\Http\Request;

class FailureController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Failure::class, 'failure');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Failure::query();

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
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $failures = $query->paginate($perPage);

        return response()->json([
            'data' => FailureResource::collection($failures),
            'pagination' => [
                'page' => $failures->currentPage(),
                'total_pages' => $failures->lastPage(),
                'per_page' => $failures->perPage(),
                'total' => $failures->total(),
            ],
            'sortable_fields' => Failure::sortableFields(),
            'filterable_fields' => Failure::filterableFields()
        ], 200);
    }

    public function store(StoreFailureRequest $request)
    {
        $failure = Failure::create($request->all());
        return response()->json(new FailureResource($failure), 201);
    }

    public function show(Failure $failure)
    {
        return response()->json(new FailureResource($failure), 200);
    }

    public function update(UpdateFailureRequest $request, Failure $failure)
    {
        $failure->update($request->all());
        return response()->json(new FailureResource($failure), 200);
    }

    public function destroy(Failure $failure)
    {
        $failure->delete();
        return response()->json([], 204);
    }
}
