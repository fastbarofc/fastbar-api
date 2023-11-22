<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return response()->json(ProductResource::collection($products), 200);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->all());
        return response()->json(new ProductResource($product), 201);
    }

    public function show(Product $product)
    {
        return response()->json(new ProductResource($product), 200);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->all());
        return response()->json(new ProductResource($product), 200);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json([], 204);
    }
}
