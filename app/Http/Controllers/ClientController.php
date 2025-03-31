<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\EventResource;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
use App\Models\User;
use App\Notifications\UserPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Client::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'name' ? 'like' : '=';
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

        $products = $query->paginate($perPage);

        return response()->json([
            'data' => ClientResource::collection($products),
            'pagination' => [
                'page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'sortable_fields' => Client::sortableFields(),
            'filterable_fields' => Client::filterableFields()
        ], 200);
    }

    public function store(StoreClientRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();

            $client = Client::create($data);
            
            if (!empty($data['has_login'])) {

                $existingUser = User::where('email', $client->email)->first();

                if ($existingUser) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Email already registered as a user.',
                    ], 422);
                }

                $deletedUser = User::withTrashed()->where('email', $client->email)->first();

                if ($deletedUser) {
                    $deletedUser->update(['email' => $client->email . '.deleted_' . time()]);
                }
                
                $randomPassword = Str::random(8);
                
                $userData = [
                    'name'     => $client->name,
                    'email'    => $client->email,
                    'password' => bcrypt($randomPassword),
                ];
                
                $user = User::create($userData);
                $client->update(['user_id' => $user->id]);
                
                $roleClient = Role::where('name', 'Client')->first();
                $user->assignRole($roleClient);
                
                $user->notify(new UserPassword($randomPassword));
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

            DB::commit();
            
            return response()->json(new ClientResource($client), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
        
    public function show(Client $client)
    {
        return response()->json(new ClientResource($client), 200);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {

        try {
            DB::beginTransaction();

            $data = $request->all();
            
            $client->update($data);

            if (!empty($data['has_login'])) {
                $existingUser = User::where('email', $client->email)->first();

                if ($existingUser && $existingUser->id !== $client->user_id) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Email already registered as a user.'
                    ], 422);
                }
                
                $deletedUser = User::withTrashed()->where('email', $client->email)->first();
                
                if ($deletedUser) {
                    $deletedUser->update(['email' => $client->email . '.deleted_' . time()]);
                }
                
                $user = $client->user;
                
                if (!$user) {
                    $randomPassword = Str::random(8);
                    
                    $userData = [
                        'name'     => $client->name,
                        'email'    => $client->email,
                        'password' => bcrypt($randomPassword),
                    ];
                    
                    $user = User::create($userData);
                    $client->update(['user_id' => $user->id]);
                    
                    $user->notify(new UserPassword($randomPassword));
                }
                
                $user->update([
                    'name'   => $client->name,
                    'email'  => $client->email,
                ]);

                $roleClient = Role::where('name', 'Client')->first();
                $user->assignRole($roleClient);
            }
                
                
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
                
                DB::commit();
                
                return response()->json(new ClientResource($client), 200);
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

    public function destroy(Client $client)
    {
        if ($client->user) {
            $client->user->delete();
        }

        $client->delete();
        return response()->json([], 204);
    }

    public function clientEvents(Request $request, $clientId)
    {
        $perPage = $request->input('per_page', 10);

        $client = Client::findOrFail($clientId);

        $query = $client->events()->getQuery();

        if ($request->has('filter_by')) {
            $filterable = json_decode($request->input('filter_by'), true);

            foreach ($filterable as $filter) {
                $field = array_key_first($filter);
                $value = $filter[$field];
                $operator = in_array($field, ['name']) ? 'like' : '=';

                if ($field == 'search') {
                    $query->where(function ($subQuery) use ($value) {
                        foreach (Event::searchableFields() as $searchableField) {
                            $subQuery->orWhere($searchableField, 'like', "%$value%");
                        }
                    });
                } else {
                    $query->filterByField($field, $value, $operator);
                }
            }
        }

        if ($request->input('order_by')) {
            $sortField = $request->input('order_by');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
    
            if (str_contains($sortField, '.')) {
                [$relation, $field] = explode('.', $sortField);
                $query->whereHas($relation)->with($relation)->orderByRelation($relation, $field, $direction);
            } else {
                $query->orderByField($sortField, $direction);
            }
        }

        $events = $query->paginate($perPage);

        return response()->json([
            'data' => EventResource::collection($events),
            'sortable_fields' => Event::sortableFields(),
            'filterable_fields' => Event::filterableFields()
        ], 200);
    }
}
