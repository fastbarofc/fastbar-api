<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use App\Models\Person;

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

        if (!empty($data['has_login'])) {
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            $client->update(['user_id' => $user->id]);
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

        if (!empty($data['has_login'])) {
            $user = User::updateOrCreate(['id' => $client->user_id], [
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => bcrypt($data['password'])
            ]);
            $client->update(['user_id' => $user->id]);
        }

        return response()->json(new ClientResource($client), 200);
    }
    
    public function destroy(Client $client)
    {
        if($client->user) {
            $client->user->delete();
        }
        $client->delete();
        return response()->json([], 204);
    }
}
