<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Resources\CollaboratorResource;
use App\Models\Collaborator;
use App\Models\User;
use App\Models\Person;
use App\Models\FisicalPerson;
use App\Models\Address;

class CollaboratorController extends Controller
{
    
    public function index()
    {
        $collaborators = Collaborator::all();
        return response()->json(CollaboratorResource::collection($collaborators), 200);
    }
    
    public function store(StoreCollaboratorRequest $request)
    {
        $data = $request->all();

        $collaborator = Collaborator::create($data);

        if (!empty($data['has_login'])) {
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            $collaborator->update(['user_id' => $user->id]);
        } 

        if (!empty($data['address'])) {
            $address = $data['address'];
            Address::create([
               'addressable_id'      => $collaborator->id,
               'addressable_type'    => Collaborator::class,
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


        return response()->json(new CollaboratorResource($collaborator), 201);
    }
    
    public function show(Collaborator $collaborator)
    {
        return response()->json(new CollaboratorResource($collaborator), 200);
    }
    
    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    {
        $data = $request->all();
        $collaborator->update($data);

        if (!empty($data['has_login'])) {
            $user = User::updateOrCreate(['id' => $collaborator->user_id], [
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => bcrypt($data['password'])
            ]);
            $collaborator->update(['user_id' => $user->id]);
        }

        if (!empty($data['address'])) {
            $address = $data['address'];
            Address::updateOrCreate(
                [
                    'addressable_id'      => $collaborator->id,
                    'addressable_type'    => Collaborator::class
                ], 
                [
                    'addressable_id'      => $collaborator->id,
                    'addressable_type'    => Collaborator::class,
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

        return response()->json(new CollaboratorResource($collaborator), 200);
    }
    
    public function destroy(Collaborator $collaborator)
    {
        if($collaborator->user) {
            $collaborator->user->delete();
        }
        $person = Person::where('id', $collaborator->person_id)->first();
        $collaborator->delete();
        return response()->json([], 204);
    }
}
