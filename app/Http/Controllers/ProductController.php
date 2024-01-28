<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProductController::class, 'product');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Product::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'soretable_fields' => Product::sortableFields()
        ], 200);
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
