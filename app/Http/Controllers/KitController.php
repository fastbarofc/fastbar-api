<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\UpdateKitRequest;
use App\Http\Resources\KitResource;
use App\Http\Resources\KitTreeResource;
use App\Models\Kit;
use Illuminate\Http\Request;

class KitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(KitController::class, 'kit');
    }

    public function index(Request $request)
    {
        $query = Kit::query();

        if (empty($request->input('filter_by'))) {
            $query->whereNull('kit_id')
                ->orderBy('name', 'asc')
                ->limit(30);
        } else {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as $field => $value) {
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
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
        }

        $kits = $query->get();

        return response()->json([
            'data' => KitResource::collection($kits),
            'sortable_fields' => Kit::sortableFields(),
            'filterable_fields' => Kit::filterableFields()
        ], 200);
    }


    public function indexTree(Request $request)
    {
        $query = Kit::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'name' ? 'like' : '=';
                    $query->filterByField($field, $value, $operator);
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $query->whereNull('kit_id');

        $kits = $query->get();

        return response()->json([
            'data' => KitTreeResource::collection($kits),
            'sortable_fields' => Kit::sortableFields(),
            'filterable_fields' => Kit::filterableFields()
        ], 200);
    }

    public function store(StoreKitRequest $request)
    {
        $kit = Kit::create($request->all());
        return response()->json(new KitResource($kit), 201);
    }

    public function show(Kit $kit)
    {
        return response()->json(new KitResource($kit), 200);
    }

    public function update(UpdateKitRequest $request, Kit $kit)
    {
        $kit->update($request->all());
        return response()->json(new KitResource($kit), 200);
    }

    public function destroy(Kit $kit)
    {
        $children = Kit::where('kit_id', $kit->id)->get();
        if ($children->count() > 0) {
            $this->destroyChildrenKit($kit);
        }
        $kit->delete();
        return response()->json([], 204);
    }

    public function destroyChildrenKit(Kit $parentKit)
    {
        $children = Kit::where('kit_id', $parentKit->id)->get();

        foreach ($children as $child) {
            $children2 = Kit::where('kit_id', $child->id)->get();
            if ($children2->count() > 0) {
                $this->destroyChildrenKit($child);
            }
            $child->delete();
        }
    }

    public function children(Kit $kit)
    {
        $children = $kit->children;
        return response()->json($children, 200);
    }
}
