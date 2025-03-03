<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\UpdateKitRequest;
use App\Http\Resources\KitResource;
use App\Http\Resources\KitTreeResource;
use App\Models\Kit;
use App\Models\Product;
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
        $query = Kit::query()->whereNull('kit_id');

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

        $kits = $query->get()->sortBy('ordination')->values();

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

    public function changeOrdination($movingItem, Request $request)
    {
        if ($request->type == 'product' || $request->moving_type == 'product') {
            return $this->changeProductOrdination($movingItem, $request);
        }

        $movingKit = Kit::find($movingItem);

        $destinationKit = Kit::find($request->id);

        if (empty($destinationKit->id)) {
            $this->shiftToFirstOrdination($movingKit);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        if ($movingKit->kit_id != $destinationKit->kit_id) {
            if ($this->isShiftingToFirtOrdination($movingKit, $destinationKit)) {
                $this->shiftToFirstOrdination($movingKit);
                return response()->json(['message' => 'Alterado com sucesso.'], 200);
            }
            return response()->json(['message' => 'Você só pode reordenar kits dentro da mesma pasta.'], 422);
        }

        $this->fixEmptyOrdination($movingKit);

        $movingKit->refresh();
        $destinationKit->refresh();

        if ($this->isAscending($movingKit, $destinationKit)) {
            $this->shiftUpward($movingKit, $destinationKit);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        $this->shiftDownward($movingKit, $destinationKit);
        return response()->json(['message' => 'Alterado com sucesso.'], 200);
    }

    public function changeProductOrdination($movingItem, Request $request)
    {
        if ($request->type == 'kit') {
            if ($request->id != $request->moving_parent_kit_id) {
                return response()->json(['message' => 'Você só pode reordenar produtos dentro do mesmo kit.'], 422);
            }
            $parentKit = Kit::find($request->moving_parent_kit_id);
            $this->shiftProductToFirstOrdination($parentKit, $movingItem);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        $destinationProduct = Product::find($request->id);

        if (empty($destinationProduct->id)) {
            return response()->json(['message' => 'Não é possível reordenar produtos para fora de um kit.'], 422);
        }

        $parentKit = Kit::find($request->moving_parent_kit_id);

        $destinationProduct = $parentKit->products()->wherePivot('product_id', $request->id)->first();
        $movingProduct = $parentKit->products()->wherePivot('product_id', $movingItem)->first();

        if ($parentKit->id != $request->kit_id) {
            return response()->json(['message' => 'Você só pode reordenar produtos dentro do mesmo kit.'], 422);
        }

        $this->fixEmptyOrdinationProduct($parentKit);

        $movingProduct->refresh();
        $destinationProduct->refresh();

        if ($this->isAscendingProduct($movingProduct, $destinationProduct)) {
            $this->shiftProductsUpward($movingProduct, $destinationProduct, $parentKit);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        $this->shiftProductsDownward($movingProduct, $destinationProduct, $parentKit);
        return response()->json(['message' => 'Alterado com sucesso.'], 200);
    }

    public function shiftProductToFirstOrdination(Kit $parentKit, $movingItem)
    {
        $products = $parentKit->products()
            ->get();

        foreach ($products as $product) {
            $product->pivot->ordination = $product->pivot->ordination + 1;
            $product->pivot->save();
        }

        $movingProduct = $parentKit->products()
            ->wherePivot('product_id', $movingItem)
            ->first();

        $movingProduct->pivot->ordination = 1;
        $movingProduct->pivot->save();
    }

    public function shiftToFirstOrdination(Kit $movingKit)
    {
        $kits = Kit::where('kit_id', $movingKit->kit_id)
            ->get();

        foreach ($kits as $kit) {
            $kit->ordination = $kit->ordination + 1;
            $kit->save();
        }

        $movingKit->ordination = 1;
        $movingKit->save();
    }

    public function isShiftingToFirtOrdination(Kit $movingKit, Kit $destinationKit)
    {
        return $movingKit->kit_id == $destinationKit->id;
    }

    public function shiftProductsUpward(Product $movingProduct, Product $destinationProduct, Kit $parentKit)
    {
        $productsToShift = $parentKit->products()
            ->whereBetween('ordination', [$destinationProduct->pivot->ordination + 1, $movingProduct->pivot->ordination - 1])
            ->get();

        foreach ($productsToShift as $product) {
            $product->pivot->ordination = $product->pivot->ordination + 1;
            $product->pivot->save();
        }

        $movingProduct->pivot->ordination = $destinationProduct->pivot->ordination + 1;
        $movingProduct->pivot->save();
    }

    public function shiftUpward(Kit $movingKit, Kit $destinationKit)
    {
        $kitsToShift = Kit::where('kit_id', $movingKit->kit_id)
            ->whereBetween('ordination', [$destinationKit->ordination + 1, $movingKit->ordination - 1])
            ->get();


        foreach ($kitsToShift as $kit) {
            $kit->ordination = $kit->ordination + 1;
            $kit->save();
        }

        $movingKit->ordination = $destinationKit->ordination + 1;
        $movingKit->save();
    }

    public function shiftProductsDownward(Product $movingProduct, Product $destinationProduct, Kit $parentKit)
    {
        $productsToShift = $parentKit->products()
        ->whereBetween('ordination', [$movingProduct->pivot->ordination + 1, $destinationProduct->pivot->ordination])
        ->get()
        ->sortBy('ordination')
        ->values();

        foreach ($productsToShift as $product) {
            $product->pivot->ordination = $product->pivot->ordination - 1;
            $product->pivot->save();
        }

        $movingProduct->pivot->ordination = $destinationProduct->pivot->ordination;
        $movingProduct->pivot->save();
    }

    public function shiftDownward(Kit $movingKit, Kit $destinationKit)
    {
        $kitsToShift = Kit::where('kit_id', $movingKit->kit_id)
            ->whereBetween('ordination', [$movingKit->ordination + 1, $destinationKit->ordination])
            ->get()
            ->sortBy('ordination')
            ->values();

        foreach ($kitsToShift as $kit) {
            $kit->ordination = $kit->ordination - 1;
            $kit->save();
        }

        $movingKit->ordination = $destinationKit->ordination;
        $movingKit->save();
    }

    public function isAscending(Kit $movingKit, Kit $destinationKit)
    {
        return $movingKit->ordination > $destinationKit->ordination;
    }

    public function isAscendingProduct(Product $movingProduct, Product $destinationProduct)
    {
        return $movingProduct->pivot->ordination > $destinationProduct->pivot->ordination;
    }

    public function fixEmptyOrdinationProduct(Kit $parentKit)
    {
        $productWithEmptyOrdination = $parentKit->products()->wherePivot('ordination', null)->get();

        $totalProducts = $parentKit->products()->wherePivot('ordination', '!=', null)->get()->count();

        foreach ($productWithEmptyOrdination as $index => $product) {
            $product->pivot->update(['ordination' => $totalProducts + $index + 1]);
        }
    }

    public function fixEmptyOrdination(Kit $movingKit)
    {
        $kitWithEmptyOrdination = Kit::where('kit_id', $movingKit->kit_id)
            ->whereNull('ordination')
            ->get()
            ->sortBy('name')
            ->values();

        $totalKits =  Kit::where('kit_id', $movingKit->kit_id)
            ->get()
            ->count();

        foreach ($kitWithEmptyOrdination as $key => $kit) {
            $kit->update(['ordination' => $totalKits + $key + 1]);
        }
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
