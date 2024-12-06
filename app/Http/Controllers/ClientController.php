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
                    $operator = $field == 'name' || $field == 'nickname' || $field == 'email' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $clients = $query->paginate($perPage);

        return response()->json([
            'data' => ClientResource::collection($clients),
            'pagination' => [
                'page' => $clients->currentPage(),
                'total_pages' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
            'sortable_fields' => Client::sortableFields(),
            'filterable_fields' => Client::filterableFields()
        ], 200);
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->all();

        $client = Client::create($data);

        if (!empty($data['has_login'])) {

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

            if (!empty($data['password'])) {
                $userData['password'] = bcrypt($data['password']);
            }

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

        if ($request->has('order_by')) {
            $orderBy = $request->input('order_by');
            $direction = $request->input('direction', 'asc');

            $query->orderBy($orderBy, $direction);
        }

        $events = $query->paginate($perPage);

        return response()->json([
            'data' => EventResource::collection($events),
            'sortable_fields' => Event::sortableFields(),
            'filterable_fields' => Event::filterableFields()
        ], 200);
    }
}
