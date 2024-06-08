<?php

namespace App\Http\Controllers;

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

        $query = EventKit::query()->where('event_id', $request->input('event_id'));

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $operator = $field == 'name' ? 'like' : '=';
                $query->filterByField($field, $value, $operator);
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $query->whereNull('event_kit_id');

        $eventKits = $query->get();


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

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventKits = $query->get();


        return response()->json([
            'data' => EventKitTreeResource::collection($eventKits),
        ], 200);
    }

    public function children(EventKit $kit)
    {
        $children = $kit->children;
        return response()->json($children, 200);
    }

    public function store(Request $request)
    {
        $kit = Kit::find($request->input('id'));
        $data = $request->all();
        $data['themed_table'] = false;
        $data['optional'] = false;
        if (empty($data['type']) || $data['type'] != 'product') {
            $eventKit = EventKit::create($data);
            $this->transformKitToEventKit($kit, $eventKit);
            return response()->json(new EventKitTreeResource($eventKit), 201);
        }
        $product = Product::find($data['id']);
        $product->eventKits()->attach($data['event_kit_id']);
        return response()->json($product, 201);
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
                $data['optional'] = false;
                $newEventKit = EventKit::create($data);
                $newKit = Kit::find($child['data']['id']);
                if ($kit && $kit->has_children) {
                    $this->transformKitToEventKit($newKit, $newEventKit);
                }
            }
        }
    }

    public function show(EventKit $eventKit)
    {
        return response()->json(new EventKitTreeResource($eventKit), 200);
    }

    public function update(Request $request, EventKit $eventKit)
    {
        $eventKit->update($request->all());
        return response()->json(new EventKitTreeResource($eventKit), 200);
    }

    public function destroy(EventKit $eventKit)
    {
        $eventKit->delete();
        return response()->json([], 204);
    }
}
