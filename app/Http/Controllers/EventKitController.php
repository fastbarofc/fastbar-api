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
        $data = $request->all();

        $data['themed_table'] = false;
        $data['optional'] = false;
        $data['checklist'] = false;

        $eventKitId = !empty($data['event_kit_id']) ? $data['event_kit_id'] : null;

        if (empty($data['type']) || $data['type'] != 'product') {
            $data['ordination'] = EventKit::where('event_kit_id', $eventKitId)->where('event_id', $data['event_id'])->get()->count() + 1;
            $eventKit = EventKit::create($data);
            $this->transformKitToEventKit($kit, $eventKit);
            return response()->json(new EventKitTreeResource($eventKit), 201);
        }

        $product = Product::find($data['id']);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        try {
            $product->eventKits()->attach($eventKitId);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error creating kit'], 500);
        }
        $product->type = 'product';
        return response()->json(['data' => $product], 201);
    }

    public function transformKitToEventKit($kit, $eventKit)
    {
        if ($kit && $kit->has_children) {
            $children = $kit->children;
            foreach ($children as $child) {
                if ($child['data']['type'] == 'product') {
                    $product = Product::find($child['data']['id']);
                    $product->eventKits()->attach($eventKit->id);
                    continue;
                }
                $data = $child['data'];
                $data['event_id'] = $eventKit->event_id;
                $data['event_kit_id'] = $eventKit->id;
                $data['themed_table'] = false;
                $data['checklist'] = false;
                $data['optional'] = false;
                $data['ordination'] = EventKit::where('event_kit_id', $data['event_kit_id'])->where('event_id', $data['event_id'])->get()->count() + 1;
                $newEventKit = EventKit::create($data);
                $newKit = Kit::find($child['data']['id']);
                if ($kit && $kit->has_children) {
                    $this->transformKitToEventKit($newKit, $newEventKit);
                }
            }
        }
    }

    public function changeOrdination(EventKit $movingEventKit, Request $request)
    {
        if ($request->type == 'product') {
            return response()->json(['message' => 'Você só pode reordenar entre kits e não produtos.'], 422);
        }

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
