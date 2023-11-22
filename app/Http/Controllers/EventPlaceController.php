<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventPlaceRequest;
use App\Http\Requests\UpdateEventPlaceRequest;
use App\Http\Resources\EventPlaceResource;
use App\Models\EventPlace;
use App\Models\Address;

class EventPlaceController extends Controller
{
    public function index()
    {
        $eventPlaces = EventPlace::all();
        return response()->json(EventPlaceResource::collection($eventPlaces), 200);
    }

    public function store(StoreEventPlaceRequest $request)
    {
        $data = $request->all();

        $eventPlace = EventPlace::create($data);

        if (!empty($data['address'])) {
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

        if (!empty($data['address'])) {
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
