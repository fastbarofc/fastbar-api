<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        return response()->json(ClientResource::collection($clients), 200);
    }
    
    public function store(StoreClientRequest $request)
    {
        $data = $request->all();

        $client = Client::create($data);

        if($client->type == Client::TYPE_FISICAL) {
            $client->fisicalPerson()->create($data);
        } else {
            $client->legalPerson()->create($data);
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
            $client->fisicalPerson()->update($data);
        } else {
            $client->legalPerson()->update($data);
        }

        return response()->json(new ClientResource($client), 200);
    }
    
    public function destroy(Client $client)
    {
        if($client->type == Client::TYPE_FISICAL) {
            $client->fisicalPerson()->delete();
        } else {
            $client->legalPerson()->delete();
        }

        $client->delete();
        return response()->json([], 204);
    }
    
}
