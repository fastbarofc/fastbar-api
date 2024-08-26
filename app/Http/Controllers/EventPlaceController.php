<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventPlaceRequest;
use App\Http\Requests\UpdateEventPlaceRequest;
use App\Http\Resources\EventPlaceResource;
use App\Models\EventPlace;
use App\Models\Address;
use Illuminate\Http\Request;

class EventPlaceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventPlaceController::class, 'eventPlace');
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventPlace::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as  $field => $value) {
                    $operator = $field == 'name' || $field == 'responsible' || $field == 'email' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventPlaces = $query->paginate($perPage);

        return response()->json([
            'data' => EventPlaceResource::collection($eventPlaces),
            'pagination' => [
                'current_page' => $eventPlaces->currentPage(),
                'total_pages' => $eventPlaces->lastPage(),
                'per_page' => $eventPlaces->perPage(),
                'total' => $eventPlaces->total(),
            ],
            'sortable_fields' => EventPlace::sortableFields(),
            'filterable_fields' => EventPlace::filterableFields()
        ], 200);
    }

    public function store(StoreEventPlaceRequest $request)
    {
        $data = $request->all();

        $eventPlace = EventPlace::create($data);

        if (!empty($data['address']) && !empty($data['address']['street'])) {
            $address = $data['address'];
            Address::create([
               'addressable_id'      => $eventPlace->id,
               'addressable_type'    => EventPlace::class,
               'street'             => $address['street'],
               'district'             => $address['district'],
               'complement'             => $address['complement'],
               'city_id'             => $address['city_id'],
               'state_id'             => $address['state_id'],
               'number'             => $address['number'],
               'zip_code'             => $address['zip_code'],
               'reference'             => $address['reference'],
            ]);
        }

        return response()->json(new EventPlaceResource($eventPlace), 201);
    }

    public function show(EventPlace $eventPlace)
    {
        return response()->json(new EventPlaceResource($eventPlace), 200);
    }

    public function update(UpdateEventPlaceRequest $request, EventPlace $eventPlace)
    {
        $data = $request->all();
        $eventPlace->update($data);

        if (!empty($data['address']) && !empty($data['address']['street'])) {
            $address = $data['address'];
            Address::updateOrCreate(
                [
                    'addressable_id'      => $eventPlace->id,
                    'addressable_type'    => EventPlace::class
                ], 
                [
                        'addressable_id'      => $eventPlace->id,
                        'addressable_type'    => EventPlace::class,
                        'street'             => $address['street'],
                        'district'             => $address['district'],
                        'complement'             => $address['complement'],
                        'city_id'             => $address['city_id'],
                        'state_id'             => $address['state_id'],
                        'number'             => $address['number'],
                        'zip_code'             => $address['zip_code'],
                        'reference'             => $address['reference'],
                ]
            );        
        }     
        
        return response()->json(new EventPlaceResource($eventPlace), 200);
    }

    
    public function destroy(EventPlace $eventPlace)
    {
        $eventPlace->delete();
        return response()->json([], 204);
    }
}
