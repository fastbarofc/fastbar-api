<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Resources\CollaboratorResource;
use Spatie\Permission\Models\Role;
use App\Models\Collaborator;
use App\Models\User;
use App\Models\File;
use App\Models\Address;
use App\Services\FileService;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CollaboratorController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Collaborator::class, 'collaborator');
    }
    
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Collaborator::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $operator = $field == 'name' || $field == 'nickname' ? 'like' : '=';
                $query->filterByField(
                    field: $field, 
                    value: $operator == 'like' ? "%$value%" : $value,
                    operator: $operator,
                );
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $events = $query->paginate($perPage);

        return response()->json([
            'data' => CollaboratorResource::collection($events),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'total_pages' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
            'sortable_fields' => Collaborator::sortableFields(),
            'filterable_fields' => Collaborator::filterableFields()
        ], 200);
    }
    
    public function store(StoreCollaboratorRequest $request)
    {
        $data = $request->all();

        $collaborator = Collaborator::create($data);

        if (!empty($data['has_login'])) {
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            $collaborator->update(['user_id' => $user->id]);
            
            $roleAdmin = Role::where('name', 'Admin')->first();
            $user->assignRole($roleAdmin);
        } 

        if (!empty($data['address']) && !empty($data['address']['street'])) {
            $address = $data['address'];
            Address::create([
               'addressable_id'   => $collaborator->id,
               'addressable_type' => Collaborator::class,
               'street'           => $address['street'],
               'district'         => $address['district'],
               'complement'       => $address['complement'],
               'city_id'          => $address['city_id'],
               'state_id'         => $address['state_id'],
               'number'           => $address['number'],
               'zip_code'         => $address['zip_code'],
               'reference'        => $address['reference'],
            ]);
        }

        if ($request->has('photo') && !empty($request->photo)) {
            try {
                $file = FileService::saveImage('collaborators', $request->photo);
                $collaborator->update(['file_id' => $file->id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        $qualifications = Qualification::all();

        foreach ($qualifications as $qualification) {
            $collaborator->qualifications()->attach($qualification->id, ['points' => 0]);
        }

        if (!empty($data['qualifications'])) {
            foreach ($data['qualifications'] as $qualification) {
                $collaborator->qualifications()->syncWithoutDetaching([$qualification['id'] => ['points' => $qualification['points']]]);
            }
        }

        return response()->json(new CollaboratorResource($collaborator), 201);
    }
    
    public function show(Collaborator $collaborator)
    {
        return response()->json(new CollaboratorResource($collaborator), 200);
    }
    
    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    {
        $data = $request->except('photo');
        $collaborator->update($data);

        if ($request->filled('photo')) {
            $photoUrl = $request->photo;
    
            $photoPath = parse_url($photoUrl, PHP_URL_PATH);
            $normalizedPhotoPath = str_replace('/storage//', '/', $photoPath);
    
            if (!isset($collaborator->file_id) || $normalizedPhotoPath !== $collaborator->file->path) {
            try {
                $file = FileService::saveImage('collaborators', $request->photo);
                $collaborator->update(['file_id' => $file->id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            }
        } else {
    
    
            if ($collaborator->file_id) {
                Storage::disk('public')->delete($collaborator->file->path);
    
                $collaborator->file->delete();
    
                $collaborator['file_id'] = null;
            }
        }

        if (!empty($data['has_login'])) {
            $user = User::updateOrCreate(['id' => $collaborator->user_id], [
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => bcrypt($data['password'])
            ]);
            $collaborator->update(['user_id' => $user->id]);

            $roleAdmin = Role::where('name', 'Admin')->first();

            if (isset($data['active'])) {
                if ($data['active']) {
                    $user->assignRole($roleAdmin);
                } else {
                    $user->removeRole($roleAdmin);
                }
            }
            
        }

        if (!empty($data['address']) && !empty($data['address']['street'])) {
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

       /*  if ($request->hasFile('photo')) {
            if ($collaborator->file_id) {
                $actualPhoto = File::where('id', $collaborator->file_id)->first();
                FileService::delete($actualPhoto);
                $actualPhoto->delete();
            }
            $photo = $request->file('photo'); 
            $photo = FileService::upload($photo, 'collaborator');
            $collaborator->update(['file_id' => $photo->id]);
        } */

        $qualifications = Qualification::all();

        foreach ($qualifications as $qualification) {
            $collaborator->qualifications()->sync([$qualification->id => ['points' => 0]]);
        }

        if (!empty($data['qualifications'])) {
            foreach ($data['qualifications'] as $qualification) {
                $collaborator->qualifications()->syncWithoutDetaching([$qualification['id'] => ['points' => $qualification['points']]]);
            }
        }

        return response()->json(new CollaboratorResource($collaborator), 200);
    }
    
    public function destroy(Collaborator $collaborator)
    {
        if($collaborator->user) {
            $collaborator->user->delete();
        }
        $collaborator->delete();
        return response()->json([], 204);
    }

    public function getFilePath(Collaborator $collaborator)
{
    $fileId = $collaborator->file_id;
    $file = File::find($fileId);

    if ($file) {
        $photoUrl = asset('storage/' . $file->path); 
        return response()->json(['photo_path' => $photoUrl]);
    } else {
        return response()->json(['photo_path' => null]);
    }
}
}
                

