<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDecoratorRequest;
use App\Http\Requests\UpdateDecoratorRequest;
use App\Http\Resources\DecoratorResource;
use App\Models\Decorator;

class DecoratorController extends Controller
{
    public function index()
    {
        $decorators = Decorator::all();
        return response()->json(DecoratorResource::collection($decorators), 200);
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
