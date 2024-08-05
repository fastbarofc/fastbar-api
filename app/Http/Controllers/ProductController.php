<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\EventKit;
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
                $operator = $field == 'name' ? 'like' : '=';
                $query->filterByField(
                    field: $field, 
                    value: $value,
                    operator: $operator,
                );
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

    public function moveToKit(Request $request)
    {
        $newKit = Kit::find($request->new_kit['id']);
        $oldKit = Kit::find($request->old_kit['id']);
        $product = Product::find($request->product['id']);

        $newKit->products()->attach($product->id);
        $oldKit->products()->detach($product->id);

        return response()->json([], 201);
    }

    public function moveToEventKit(Request $request)
    {        
        $newEventKit = EventKit::find($request->new_event_kit['id']);
        $oldEventKit = EventKit::find($request->old_event_kit['id']);
        $product = Product::find($request->product['id']);
        $newEventKit->products()->attach($product->id);
        $oldEventKit->products()->detach($product->id);

    }

    public function attachToEventKit(Request $request)
    {
        $eventKit = EventKit::find($request->event_kit_id);
        $eventKit->products()->attach($request->product_id);
    }

    public function attachToKit(Request $request)
    {
        $kit = Kit::find($request->kit_id);
        $kit->products()->attach($request->product_id);
    }

    public function detachFromEventKit(Request $request)
    {
        $eventKit = EventKit::find($request->event_kit_id);
        $eventKit->products()->detach($request->product_id);
    }

    public function detachFromKit(Request $request)
    {
        $kit = Kit::find($request->kit_id);
        $kit->products()->detach($request->product_id);
    }

    public function getFilePath(Product $product)
{
    $fileId = $product->file_id;
    $file = File::find($fileId);

    if ($file) {
        $photoUrl = asset('storage/' . $file->path); 
        return response()->json(['photo_path' => $photoUrl]);
    } else {
        return response()->json(['photo_path' => null]);
    }
}


}
