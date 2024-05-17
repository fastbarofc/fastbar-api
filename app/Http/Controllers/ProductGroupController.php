<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductGroupRequest;
use App\Http\Requests\UpdateProductGroupRequest;
use App\Http\Resources\ProductGroupResource;
use App\Models\ProductGroup;
use Illuminate\Http\Request;

class ProductGroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProductGroupController::class, 'productGroup');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = ProductGroup::query();

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

        $productGroups = $query->paginate($perPage);

        return response()->json([
            'data' => ProductGroupResource::collection($productGroups),
            'pagination' => [
                'current_page' => $productGroups->currentPage(),
                'total_pages' => $productGroups->lastPage(),
                'per_page' => $productGroups->perPage(),
                'total' => $productGroups->total(),
            ],
            'sortable_fields' => ProductGroup::sortableFields(),
            'filterable_fields' => ProductGroup::filterableFields()
        ], 200);
    }

    public function store(StoreProductGroupRequest $request)
    {
        $productGroup = ProductGroup::create($request->all());
        return response()->json(new ProductGroupResource($productGroup), 201);
    }

    public function show(ProductGroup $productGroup)
    {
        return response()->json(new ProductGroupResource($productGroup), 200);
    }

    public function update(UpdateProductGroupRequest $request, ProductGroup $productGroup)
    {
        $productGroup->update($request->all());
        return response()->json(new ProductGroupResource($productGroup), 200);
    }

    public function destroy(ProductGroup $productGroup)
    {
        $productGroup->delete();
        return response()->json([], 204);
    }
}
