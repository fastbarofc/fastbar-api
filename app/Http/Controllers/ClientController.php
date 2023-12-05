<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\FisicalClient;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Address;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        if (!empty($request->type) && $request->type == Client::TYPE_FISICAL) {
            $fisicalClients = Client::where('type', Client::TYPE_FISICAL)->get();

            $perPage = $request->input('per_page', 10);
            $clients = Client::paginate($perPage);

            return response()->json([
                'data' => ClientResource::collection($fisicalClients),
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'total_pages' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                ],
            ], 200);
        }
        
        if (!empty($request->type) && $request->type == Client::TYPE_LEGAL) {
            $legalClients = Client::where('type', Client::TYPE_LEGAL)->get();

            $perPage = $request->input('per_page', 10);
            $clients = Client::paginate($perPage);

            return response()->json([
                'data' => ClientResource::collection($legalClients),
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'total_pages' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                ],
            ], 200);
        }

        $perPage = $request->input('per_page', 10);
        $clients = Client::paginate($perPage);

        return response()->json([
            'data' => ClientResource::collection($clients),                
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'total_pages' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        ], 200);
    }
    
    public function store(StoreClientRequest $request)
    {
        $data = $request->all();

        $client = Client::create($data);

        if($client->type == Client::TYPE_FISICAL) {
            $client->fisicalClient()->create($data);
        } else {
            $client->legalClient()->create($data);
        }

        if (!empty($data['address'])) {
            $address = $data['address'];
            Address::create([
               'addressable_id'      => $client->id,
               'addressable_type'    => Client::class,
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

        return response()->json(new ClientResource($client), 201);
    }
    
    public function show(Client $client)
    {
        return response()->json(new ClientResource($client), 200);
    }
    
    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->all();
        $client->update($data);

        if($client->type == Client::TYPE_FISICAL) {
            $client->fisicalClient()->update($data);
        } else {
            $client->legalClient()->update($data);
        }

        return response()->json(new ClientResource($client), 200);
    }
    
    public function destroy(Client $client)
    {
        if($client->type == Client::TYPE_FISICAL) {
            $client->fisicalClient()->delete();
        } else {
            $client->legalClient()->delete();
        }

        $client->delete();
        return response()->json([], 204);
    }
}
