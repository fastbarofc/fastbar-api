<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductSubgroupRequest;
use App\Http\Requests\UpdateProductSubgroupRequest;
use App\Http\Resources\ProductSubgroupResource;
use App\Models\ProductSubgroup;
use Illuminate\Http\Request;

class ProductSubgroupController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = ProductSubgroup::query();

        if ($request->filled('product_group_id')) {
            $query->where('product_group_id', $request->input('product_group_id'));
        }

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

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        if ($perPage != 0) {
            $subgroups = $query->paginate($perPage);
            $pagination = [
                'page'        => $subgroups->currentPage(),
                'total_pages' => $subgroups->lastPage(),
                'per_page'    => $subgroups->perPage(),
                'total'       => $subgroups->total(),
            ];
        } else {
            $subgroups  = $query->get();
            $pagination = null;
        }

        return response()->json([
            'data'              => ProductSubgroupResource::collection($subgroups),
            'pagination'        => $pagination,
            'sortable_fields'   => ProductSubgroup::sortableFields(),
            'filterable_fields' => ProductSubgroup::filterableFields(),
        ], 200);
    }

    public function store(StoreProductSubgroupRequest $request)
    {
        $subgroup = ProductSubgroup::create($request->validated());
        return response()->json(new ProductSubgroupResource($subgroup), 201);
    }

    public function show(ProductSubgroup $productSubgroup)
    {
        return response()->json(new ProductSubgroupResource($productSubgroup), 200);
    }

    public function update(UpdateProductSubgroupRequest $request, ProductSubgroup $productSubgroup)
    {
        $productSubgroup->update($request->validated());
        return response()->json(new ProductSubgroupResource($productSubgroup), 200);
    }

    public function destroy(ProductSubgroup $productSubgroup)
    {
        $productSubgroup->delete();
        return response()->json([], 204);
    }
}
