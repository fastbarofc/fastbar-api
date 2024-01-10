<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Client;
use App\Models\User;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Client::query();

        if($request->input('type')) {
            $query->where('type', $request->input('type'));
        }

        if($request->input('email')) {
            $query->where('email', $request->input('email'));
        }

        if($request->input('name')) {
            $query->where('name', $request->input('name'));
        }

        if($request->input('birth_date')) {
            $query->where('birth_date', $request->input('birth_date'));
        }

        if($request->input('nickname')) {
            $query->where('nickname', $request->input('nickname'));
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $clients = $query->paginate($perPage);

        return response()->json([
            'data' => ClientResource::collection($clients),                
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'total_pages' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
            'soretable_fields' => Client::sortableFields()
        ], 200);
    }
    
    public function store(StoreClientRequest $request)
    {
        $data = $request->all();

        $client = Client::create($data);

        $randomPassword = Str::random(8);

        if (!empty($data['has_login'])) {
            $userData = [
                'name'     => $client->name,
                'email'    => $client->email,
                'password' => bcrypt($randomPassword),
            ];
    
            $user = User::create($userData);
            $client->update(['user_id' => $user->id]);
    
            $roleClient = Role::where('name', 'Client')->first();
            $user->assignRole($roleClient);
        }

        if (!empty($data['address']) && !empty($data['address']['street'])) {
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

        if (!empty($data['address']) && !empty($data['address']['street'])) {
            $address = $data['address'];
            Address::updateOrCreate(
                [
                    'addressable_id'      => $client->id,
                    'addressable_type'    => Client::class
                ], 
                [
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
                ]
            );        
        }

        if (!empty($data['has_login'])) {
            $userData = [
                'name'     => $client->name,
                'email'    => $client->email,
            ];
    
            $user = User::updateOrCreate(
                [
                    'id' => $client->user_id
                ],
                $userData
            );
        }

        return response()->json(new ClientResource($client), 200);
    }
    
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json([], 204);
    }
}
