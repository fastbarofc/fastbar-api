<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDecoratorRequest;
use App\Http\Requests\UpdateDecoratorRequest;
use App\Http\Resources\DecoratorResource;
use Illuminate\Http\Request;
use App\Models\Decorator;

class DecoratorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $decorators = Decorator::paginate($perPage);

        return response()->json([
            'data' => DecoratorResource::collection($decorators),
            'pagination' => [
                'current_page' => $decorators->currentPage(),
                'total_pages' => $decorators->lastPage(),
                'per_page' => $decorators->perPage(),
                'total' => $decorators->total(),
            ],
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
