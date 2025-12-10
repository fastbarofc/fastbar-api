<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventKitRequest;
use App\Http\Requests\UpdateEventKitRequest;
use App\Http\Resources\EventKitTreeResource;
use App\Http\Resources\EventKitResource;
use App\Http\Resources\ProductResource;
use App\Models\EventKit;
use App\Models\Kit;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventKitController extends Controller
{
    public function __construct()
    {
        //$this->authorizeResource(EventKitController::class, 'event_kit'); TODO: Implementar
    }

    public function index(Request $request)
    {

        $query = EventKit::query()->where('event_id', $request->input('event_id'))->whereNull('event_kit_id');

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $operator = $field == 'name' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

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

        $eventKits = $query->get()->sortBy('ordination')->values();


        return response()->json([
            'data' => EventKitResource::collection($eventKits),
            'sortable_fields' => EventKit::sortableFields(),
            'filterable_fields' => EventKit::filterableFields()
        ], 200);
    }

    public function indexTree(Request $request)
    {
        $query = EventKit::query()->whereNull('event_kit_id')->where('event_id', $request->input('event_id'));

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        $eventKits = $query->get()->sortBy('ordination')->values();


        return response()->json([
            'data' => EventKitTreeResource::collection($eventKits),
        ], 200);
    }

    public function children(EventKit $kit)
    {
        $children = $kit->children;
        return response()->json($children, 200);
    }

    public function store(StoreEventKitRequest $request)
    {
        $kit = Kit::find($request->input('id'));

        if (empty($kit) && empty($request->input('type'))) {
            $eventKit = EventKit::create($request->all());
            return response()->json(new EventKitTreeResource($eventKit), 201);
        }

        $data = $request->all();

        if (empty($data['type']) || $data['type'] != 'product') {
            $eventKitId = $this->checkKitParent(null, $kit, $data['event_id']);
            $eventKit = EventKit::find($eventKitId);
            $ordination = $this->getKitOrdination($eventKitId);
            $this->transformKitToEventKit($kit, $eventKit);
            $eventKit = EventKit::find($eventKitId);
            while (!empty($eventKit->event_kit_id)) {
                $eventKit = EventKit::find($eventKit->event_kit_id);
            }
            return response()->json(new EventKitTreeResource($eventKit), 201);
        }

        $product = Product::find($data['id']);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $kit = Kit::find($data['kit_id']);
        $eventKitId = $this->checkKitParent(null, $kit, $data['event_id']);
        $ordination = $this->getProductOrdination($eventKitId);

        $kitProduct = $kit->products()->where('product_id', $data['id'])->first();

        $pivotData = [
            'ordination' => $ordination,
            'optional' => $kitProduct ? ($kitProduct->pivot->optional ?? false) : false,
            'checklist' => $kitProduct ? ($kitProduct->pivot->checklist ?? false) : false,
            'observation' => $kitProduct ? ($kitProduct->pivot->observation ?? null) : null,
        ];

        $product->eventKits()->attach($eventKitId, $pivotData);
        $eventKit = EventKit::find($eventKitId);
        while (!empty($eventKit->event_kit_id)) {
            $eventKit = EventKit::find($eventKit->event_kit_id);
        }
        return response()->json(new EventKitTreeResource($eventKit), 201);

        try {
            $ordination = $this->getProductOrdination($eventKitId);
            $product->eventKits()->attach($eventKitId, ['ordination' => $ordination]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error creating kit'], 500);
        }
        $product->type = 'product';
        return response()->json(['data' => $product], 201);
    }

    private function getKitOrdination($eventKitId)
    {
        $eventKit = EventKit::find($eventKitId);
        $kits = EventKit::where('event_kit_id', $eventKit->event_kit_id)->get();
        return $kits->count() + 1;
    }

    private function getProductOrdination($eventKitId)
    {
        $eventKit = EventKit::find($eventKitId);
        $this->fixProductEmptyOrdination($eventKit);
        $products = $eventKit->products;
        return $products->count() + 1;
    }

    private function fixProductEmptyOrdination($eventKit)
    {
        $products = $eventKit->products()->wherePivot('ordination', null)->get();
        $totalProducts = $eventKit->products()->wherePivot('ordination', '!=', null)->get()->count();
        foreach ($products as $index => $product) {
            $product->pivot->update(['ordination' => $totalProducts + $index + 1]);
        }
    }

    public function checkKitParent($parentId, $kit, $eventId)
    {
        if (!empty($kit->kit_id)) {
            $parentKit = Kit::find($kit->kit_id);
            if ($parentKit) {
                $parentId = $this->checkKitParent($parentId, $parentKit, $eventId);
            }
        }

        $eventKit = EventKit::where('event_kit_id', $parentId)->where('event_id', $eventId)->where('name', $kit->name)->first();

        if (empty($eventKit)) {
            $eventKit = EventKit::create([
                'name' => $kit->name,
                'event_id' => $eventId,
                'event_kit_id' => $parentId,
                'ordination' => EventKit::where('event_kit_id', $parentId)->where('event_id', $eventId)->count() + 1,
                'themed_table' => $kit->themed_table ?? false,
                'optional' => $kit->optional ?? false,
                'checklist' => $kit->checklist ?? false,
                'observation' => $kit->observation ?? null,
            ]);
        }

        return $eventKit->id;
    }

    public function transformKitToEventKit($kit, $eventKit)
    {
        if ($kit && $kit->has_children) {
            $children = $kit->children;
            foreach ($children as $child) {
                if ($child['data']['type'] == 'product') {
                    $product = Product::find($child['data']['id']);
                    $ordination = $this->getProductOrdination($eventKit->id);

                    $kitProduct = $kit->products()->where('product_id', $child['data']['id'])->first();

                    $pivotData = [
                        'ordination' => $ordination,
                        'optional' => $kitProduct ? ($kitProduct->pivot->optional ?? false) : ($child['data']['optional'] ?? false),
                        'checklist' => $kitProduct ? ($kitProduct->pivot->checklist ?? false) : ($child['data']['checklist'] ?? false),
                        'observation' => $kitProduct ? ($kitProduct->pivot->observation ?? null) : ($child['data']['observation'] ?? null),
                    ];

                    $product->eventKits()->attach($eventKit->id, $pivotData);
                    continue;
                }
                $data = $child['data'];
                $data['event_id'] = $eventKit->event_id;
                $data['event_kit_id'] = $eventKit->id;

                $originalKit = Kit::find($child['data']['id']);
                if ($originalKit) {
                    $data['themed_table'] = $originalKit->themed_table ?? false;
                    $data['optional'] = $originalKit->optional ?? false;
                    $data['checklist'] = $originalKit->checklist ?? false;
                    $data['observation'] = $originalKit->observation ?? null;
                } else {
                    $data['themed_table'] = $child['data']['themed_table'] ?? false;
                    $data['optional'] = $child['data']['optional'] ?? false;
                    $data['checklist'] = $child['data']['checklist'] ?? false;
                    $data['observation'] = $child['data']['observation'] ?? null;
                }

                $data['ordination'] = EventKit::where('event_kit_id', $data['event_kit_id'])->where('event_id', $data['event_id'])->get()->count() + 1;
                $newEventKit = EventKit::create($data);
                $newKit = Kit::find($child['data']['id']);
                if ($newKit && $newKit->has_children) {
                    $this->transformKitToEventKit($newKit, $newEventKit);
                }
            }
        }
    }

    public function changeOrdination($movingItem, Request $request)
    {
        if ($request->type == 'product' || $request->moving_type == 'product') {
            return $this->changeProductOrdination($movingItem, $request);
        }

        $movingEventKit = EventKit::find($movingItem);
        $destinationKit = EventKit::find($request->id);

        if (empty($destinationKit->id)) {
            $this->shiftToFirstOrdination($movingEventKit);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        if ($movingEventKit->event_kit_id != $destinationKit->event_kit_id) {
            if ($this->isShiftingToFirtOrdination($movingEventKit, $destinationKit)) {
                $this->shiftToFirstOrdination($movingEventKit);
                return response()->json(['message' => 'Alterado com sucesso.'], 200);
            }
            return response()->json(['message' => 'Você só pode reordenar kits dentro da mesma pasta.'], 422);
        }

        $this->fixEmptyOrdination($movingEventKit);

        $movingEventKit->refresh();
        $destinationKit->refresh();

        if ($this->isAscending($movingEventKit, $destinationKit)) {
            $this->shiftUpward($movingEventKit, $destinationKit);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        $this->shiftDownward($movingEventKit, $destinationKit);
        return response()->json(['message' => 'Alterado com sucesso.'], 200);
    }

    public function changeProductOrdination($movingItem, $request)
    {
        if ($request->type == 'kit') {
            if ($request->id != $request->moving_parent_event_kit_id) {
                return response()->json(['message' => 'Você só pode reordenar produtos dentro do mesmo kit.'], 422);
            }
            $parentKit = EventKit::find($request->moving_parent_event_kit_id);
            $this->shiftProductToFirstOrdination($parentKit, $movingItem);
            return response()->json(['message' => 'Alterado com sucesso.'], 200);
        }

        $destinationProduct = Product::find($request->id);

        if (empty($destinationProduct->id)) {
            return response()->json(['message' => 'Não é possível reordenar produtos para fora de um kit.'], 422);
        }

        $parentKit = EventKit::find($request->moving_parent_event_kit_id);

        $destinationProduct = $parentKit->products()->wherePivot('product_id', $request->id)->first();
        $movingProduct = $parentKit->products()->wherePivot('product_id', $movingItem)->first();

        if ($parentKit->id != $request->event_kit_id) {
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

    public function shiftProductsDownward(Product $movingProduct, Product $destinationProduct, EventKit $parentKit)
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


    public function isAscendingProduct(Product $movingProduct, Product $destinationProduct)
    {
        return $movingProduct->pivot->ordination > $destinationProduct->pivot->ordination;
    }

    public function shiftProductsUpward(Product $movingProduct, Product $destinationProduct, EventKit $parentKit)
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

    public function fixEmptyOrdinationProduct(EventKit $parentKit)
    {
        $productWithEmptyOrdination = $parentKit->products()->wherePivot('ordination', null)->get();

        $totalProducts = $parentKit->products()->wherePivot('ordination', '!=', null)->get()->count();

        foreach ($productWithEmptyOrdination as $index => $product) {
            $product->pivot->update(['ordination' => $totalProducts + $index + 1]);
        }
    }

    public function shiftProductToFirstOrdination(EventKit $parentKit, $movingItem)
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

    public function shiftToFirstOrdination(EventKit $movingEventKit)
    {
        $eventKits = EventKit::where('event_id', $movingEventKit->event_id)
            ->where('event_kit_id', $movingEventKit->event_kit_id)
            ->get();

        foreach ($eventKits as $kit) {
            $kit->ordination = $kit->ordination + 1;
            $kit->save();
        }

        $movingEventKit->ordination = 1;
        $movingEventKit->save();
    }

    public function isShiftingToFirtOrdination(EventKit $movingEventKit, EventKit $destinationKit)
    {
        return $movingEventKit->event_kit_id == $destinationKit->id;
    }

    public function shiftUpward(EventKit $movingEventKit, EventKit $destinationKit)
    {
        $kitsToShift = EventKit::where('event_id', $movingEventKit->event_id)
            ->where('event_kit_id', $movingEventKit->event_kit_id)
            ->whereBetween('ordination', [$destinationKit->ordination + 1, $movingEventKit->ordination - 1])
            ->get();


        foreach ($kitsToShift as $kit) {
            $kit->ordination = $kit->ordination + 1;
            $kit->save();
        }

        $movingEventKit->ordination = $destinationKit->ordination + 1;
        $movingEventKit->save();
    }

    public function shiftDownward(EventKit $movingEventKit, EventKit $destinationKit)
    {
        $kitsToShift = EventKit::where('event_id', $movingEventKit->event_id)
            ->where('event_kit_id', $movingEventKit->event_kit_id)
            ->whereBetween('ordination', [$movingEventKit->ordination + 1, $destinationKit->ordination])
            ->get()
            ->sortBy('ordination')
            ->values();

        foreach ($kitsToShift as $kit) {
            $kit->ordination = $kit->ordination - 1;
            $kit->save();
        }

        $movingEventKit->ordination = $destinationKit->ordination;
        $movingEventKit->save();
    }

    public function isAscending(EventKit $movingEventKit, EventKit $destinationKit)
    {
        return $movingEventKit->ordination > $destinationKit->ordination;
    }

    public function fixEmptyOrdination(EventKit $movingEventKit)
    {
        $eventKitWithEmptyOrdination = EventKit::where('event_id', $movingEventKit->event_id)
            ->where('event_kit_id', $movingEventKit->event_kit_id)
            ->whereNull('ordination')
            ->get()
            ->sortBy('name')
            ->values();

        $totalKits =  EventKit::where('event_id', $movingEventKit->event_id)
            ->where('event_kit_id', $movingEventKit->event_kit_id)
            ->get()
            ->count();

        foreach ($eventKitWithEmptyOrdination as $key => $eventKit) {
            $eventKit->update(['ordination' => $totalKits + $key + 1]);
        }
    }

    public function show(EventKit $eventKit)
    {
        return response()->json(new EventKitTreeResource($eventKit), 200);
    }

    public function updateProductEventKit(Request $request)
    {
        $data = $request->all();
        unset($data['name']);
        unset($data['type']);
        $product = Product::find($data['id']);
        unset($data['id']);
        unset($data['themed_table']);
        $product->eventKits()->updateExistingPivot($data['event_kit_id'], $data);
        return response()->json(['message' => 'Alterado com sucesso.'], 200);
    }


    public function update(UpdateEventKitRequest $request, EventKit $eventKit)
    {
        if (!$eventKit) {
            return response()->json(['error' => 'Kit não encontrado'], 404);
        }
        $eventKit->update($request->all());
        return response()->json(new EventKitTreeResource($eventKit), 200);
    }

    public function destroy(EventKit $eventKit)
    {
        $children = EventKit::where('event_kit_id', $eventKit->id)->get();
        if ($children->count() > 0) {
            $this->destroyChildrenKit($eventKit);
        }
        $eventKit->delete();
        return response()->json([], 204);
    }

    public function destroyChildrenKit(EventKit $parentKit)
    {
        $children = EventKit::where('event_kit_id', $parentKit->id)->get();

        foreach ($children as $child) {
            $children2 = EventKit::where('event_kit_id', $child->id)->get();
            if ($children2->count() > 0) {
                $this->destroyChildrenKit($child);
            }
            $child->delete();
        }
    }
}
