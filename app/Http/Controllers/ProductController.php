<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\File;
use App\Models\Product;
use App\Models\Kit;
use Illuminate\Http\Request;
use App\Services\FileService;
use Illuminate\Support\Facades\Storage;

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

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

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
            'sortable_fields' => Product::sortableFields(),
            'filterable_fields' => Product::filterableFields()
        ], 200);
    }

    public function store(StoreProductRequest $request)
    {
        $productData = $request->all();
        dd($productData);
        $product = Product::create($productData);
    
        if ($request->has('photo') && !empty($request->photo)) {
            try {
                $file = FileService::saveImage('products', $request->photo);
                $product->update(['file_id' => $file->id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }
    
        return response()->json(new ProductResource($product), 201);
    }

    public function show(Product $product)
    {
        return response()->json(new ProductResource($product), 200);
    }

    public function update(UpdateProductRequest $request, Product $product)
{

    $productData = $request->except('photo');

    if ($request->filled('photo')) {
        $photoUrl = $request->photo;

        $photoPath = parse_url($photoUrl, PHP_URL_PATH);
        $normalizedPhotoPath = str_replace('/storage//', '/', $photoPath);

        if (!isset($product->file_id) || $normalizedPhotoPath !== $product->file->path) {
        try {
            $file = FileService::saveImage('products', $request->photo);
            $productData['file_id'] = $file->id;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        }
    } else {


        if ($product->file_id) {
            Storage::disk('public')->delete($product->file->path);

            $product->file->delete();

            $productData['file_id'] = null;
        }
    }

    $product->update($productData);

    return response()->json(new ProductResource($product), 200);
}



    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json([], 204);
    }

    public function addToKit(Product $product, Kit $kit)
    {
        $kit->products()->attach($product->id);
        return response()->json([], 201);
    }

    public function getFilePath(Product $product)
{
    $fileId = $product->file_id;
    $file = File::find($fileId);

    if ($file) {
        $photoUrl = asset('storage/' . $file->path); // Gera a URL completa da imagem
        return response()->json(['photo_path' => $photoUrl]);
    } else {
        return response()->json(['photo_path' => null]);
    }
}


}
