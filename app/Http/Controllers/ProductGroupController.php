<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductGroupRequest;
use App\Http\Requests\UpdateProductGroupRequest;
use App\Http\Resources\ProductGroupResource;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductGroupController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(ProductGroupController::class, 'productGroup');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = ProductGroup::withCount(['subgroups', 'products']);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField($field, $value);
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        if ($perPage != 0) {
            $productGroups = $query->paginate($perPage);
            $pagination = [
                'page' => $productGroups->currentPage(),
                'total_pages' => $productGroups->lastPage(),
                'per_page' => $productGroups->perPage(),
                'total' => $productGroups->total(),
            ];
        } else {
            $productGroups = $query->get();
            $pagination = null;
        }

        return response()->json([
            'data' => ProductGroupResource::collection($productGroups),
            'pagination' => $pagination,
            'sortable_fields' => ProductGroup::sortableFields(),
            'filterable_fields' => ProductGroup::filterableFields()
        ], 200);
    }

    public function store(StoreProductGroupRequest $request)
    {
        $productGroup = ProductGroup::create($request->validated());
        return response()->json(new ProductGroupResource($productGroup->loadCount(['subgroups', 'products'])), 201);
    }

    public function show(ProductGroup $productGroup)
    {
        return response()->json(new ProductGroupResource($productGroup->loadCount(['subgroups', 'products'])), 200);
    }

    public function update(UpdateProductGroupRequest $request, ProductGroup $productGroup)
    {
        $productGroup->update($request->all());
        return response()->json(new ProductGroupResource($productGroup->loadCount(['subgroups', 'products'])), 200);
    }

    public function destroy(ProductGroup $productGroup)
    {
        $inUse = $productGroup->products()->exists()
            || Product::whereIn('product_subgroup_id', $productGroup->subgroups()->select('id'))->exists();

        if ($inUse) {
            return response()->json([
                'message' => 'Não é possível excluir o grupo, pois existem produtos vinculados a ele ou aos seus subgrupos.',
            ], 422);
        }

        DB::transaction(function () use ($productGroup) {
            $productGroup->subgroups->each->delete();
            $productGroup->delete();
        });

        return response()->json([], 204);
    }
}
