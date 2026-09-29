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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(ProductController::class, 'product');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Product::withCount('components');

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'name' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $products = $query->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products),
            'pagination' => [
                'page' => $products->currentPage(),
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
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($request->all());
            $this->syncComposition($product, $request);

            return $product;
        });

        if ($request->has('photo') && !empty($request->photo)) {
            try {
                $file = FileService::saveImage('products', $request->photo);
                $product->update(['file_id' => $file->id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        return response()->json(new ProductResource($this->loadDetails($product)), 201);
    }

    public function show(Product $product)
    {
        return response()->json(new ProductResource($this->loadDetails($product)), 200);
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

        DB::transaction(function () use ($product, $productData, $request) {
            $product->update($productData);
            $this->syncComposition($product, $request);
        });

        return response()->json(new ProductResource($this->loadDetails($product)), 200);
    }

    public function destroy(Product $product)
    {
        $composedProducts = $product->composedProducts()->pluck('name');

        if ($composedProducts->isNotEmpty()) {
            return response()->json([
                'message' => 'Não é possível excluir o produto, pois ele faz parte da composição de: ' . $composedProducts->join(', ') . '.',
            ], 422);
        }

        $product->delete();
        return response()->json([], 204);
    }

    private function syncComposition(Product $product, Request $request): void
    {
        if ($product->composition_item) {
            $product->components()->sync([]);
            return;
        }

        if (!$request->has('composition')) {
            return;
        }

        $product->components()->sync(
            collect($request->input('composition', []))
                ->mapWithKeys(fn ($item) => [$item['product_id'] => ['quantity' => $item['quantity']]])
                ->all()
        );
    }

    private function loadDetails(Product $product): Product
    {
        return $product->load(['components.unit', 'composedProducts'])->loadCount('components');
    }

    private function fixProductEmptyOrdination(EventKit | Kit $kit)
    {
        $products = $kit->products()->wherePivot('ordination', null)->get();
        $totalProducts = $kit->products()->wherePivot('ordination', '!=', null)->get()->count();
        foreach ($products as $index => $product) {
            $product->pivot->update(['ordination' => $totalProducts + $index + 1]);
        }
    }

    public function moveToKit(Request $request)
    {
        $newKit = Kit::find($request->new_kit['id']);
        $oldKit = Kit::find($request->old_kit['id']);
        $product = Product::find($request->product['id']);

        $ordination = $this->getProductOrdination($newKit->id);
        $newKit->products()->attach($product->id, [
            'ordination' => $ordination
        ]);
        $oldKit->products()->detach($product->id);

        return response()->json([], 201);
    }

    public function moveToEventKit(Request $request)
    {
        $newEventKit = EventKit::find($request->new_event_kit['id']);
        $oldEventKit = EventKit::find($request->old_event_kit['id']);
        $product = Product::find($request->product['id']);
        $ordination = $this->getProductOrdination($newEventKit);
        $newEventKit->products()->attach($product->id, [
            'ordination' => $ordination
        ]);
        $oldEventKit->products()->detach($product->id);
    }

    private function getProductOrdination(EventKit | Kit $kit)
    {
        $this->fixProductEmptyOrdination($kit);
        $products = $kit->products;
        return $products->count() + 1;
    }

    public function attachToEventKit(Request $request)
    {
        $eventKit = EventKit::find($request->event_kit_id);

        $hasProduct = $eventKit->products()->where('product_id', $request->product_id)->exists();

        if ($hasProduct) {
            return response()->json(['message' => 'Produto já adicionado ao kit'], 400);
        }
        $ordination = $this->getProductOrdination($eventKit);
        $eventKit->products()->attach($request->product_id, [
            'ordination' => $ordination
        ]);
    }

    public function attachToKit(Request $request)
    {
        $kit = Kit::find($request->kit_id);

        $hasProduct = $kit->products()->where('product_id', $request->product_id)->exists();

        if ($hasProduct) {
            return response()->json(['message' => 'Produto já adicionado ao kit'], 400);
        }

        $ordination = $this->getProductOrdination($kit);
        $pivotData = [
            'ordination' => $ordination,
            'optional' => $request->input('optional', false),
            'checklist' => $request->input('checklist', false),
            'observation' => $request->input('observation'),
        ];

        $kit->products()->attach($request->product_id, $pivotData);

        return response()->json(['message' => 'Produto adicionado ao kit com sucesso'], 200);
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
