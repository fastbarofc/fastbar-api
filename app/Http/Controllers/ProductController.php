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

class ProductController extends Controller
{
    public function __construct()
    {
        #$this->authorizeResource(ProductController::class, 'product');
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
        $product = Product::create($productData);
    
        if ($request->has('photo') && !empty($request->photo)) {
            try {
                $file = FileService::saveImage($product, 'file_id', 'products', $request->photo);
                $product->update(['file_id' => $file->id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }
    
        return response()->json(new ProductResource($product), 201);
    }


private function getImageExtensionFromBase64($base64String)
{
    // Obtenha os primeiros bytes da string base64
    $data = explode(',', $base64String)[0];

    // Remova o cabeçalho "data:image/png;base64," e obtenha o tipo do arquivo
    $mime = str_replace('data:image/', '', $data);

    // Converte o tipo do arquivo para a extensão correspondente
    switch ($mime) {
        case 'jpeg':
        case 'jpg':
            return 'jpg';
        case 'png':
            return 'png';
        case 'gif':
            return 'gif';
        default:
            return null; // Extensão desconhecida
    }
}

    

    
    

    public function show(Product $product)
    {
        return response()->json(new ProductResource($product), 200);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $productData = $request->all();

        $product->update($productData);

        if (isset($productData['photo']) && !empty($productData['photo'])) {
            // Decodifica a string base64 para obter os dados binários da imagem
            $imageData = base64_decode($productData['photo']);
    
            // Salva a imagem em um diretório temporário
            $tempImagePath = sys_get_temp_dir() . '/' . uniqid() . '.png';
            file_put_contents($tempImagePath, $imageData);
    
            // Faz o upload da imagem usando o FileService
            $file = FileService::upload($tempImagePath, 'products');
    
            $product->update(['file_id' => $file->id]);
    
            // Remove o campo 'photo' dos dados do produto, pois não é mais necessário
            unset($productData['photo']);
        }
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
        return response()->json(['photo_path' => $file->path]);
    } else {
        return response()->json(['photo_path' => null]);
    }
}

}
