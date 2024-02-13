<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDecoratorRequest;
use App\Http\Requests\UpdateDecoratorRequest;
use App\Http\Resources\DecoratorResource;
use Illuminate\Http\Request;
use App\Models\Decorator;

class DecoratorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DecoratorController::class, 'decorator');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Decorator::query();

        if ($request->input('filter_by')) {
            $filterableArray = $request->input('filter_by');
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $decorators = $query->paginate($perPage);

        return response()->json([
            'data' => DecoratorResource::collection($decorators),
            'pagination' => [
                'current_page' => $decorators->currentPage(),
                'total_pages' => $decorators->lastPage(),
                'per_page' => $decorators->perPage(),
                'total' => $decorators->total(),
            ],
            'sortable_fields' => Decorator::sortableFields(),
            'filterable_fields' => Decorator::filterableFields()
        ], 200);
    }

    public function store(StoreDecoratorRequest $request)
    {
        $decorator = Decorator::create($request->all());
        return response()->json(new DecoratorResource($decorator), 201);
    }

    public function show(Decorator $decorator)
    {
        return response()->json(new DecoratorResource($decorator), 200);
    }

    public function update(UpdateDecoratorRequest $request, Decorator $decorator)
    {
        $decorator->update($request->all());
        return response()->json(new DecoratorResource($decorator), 200);
    }

    public function destroy(Decorator $decorator)
    {
        $decorator->delete();
        return response()->json([], 204);
    }
}
