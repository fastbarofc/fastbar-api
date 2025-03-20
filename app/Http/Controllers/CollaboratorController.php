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
use App\Models\BankDetail;
use App\Models\DataBank;
use App\Models\PixKey;
use App\Services\FileService;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\UserPassword;
use Illuminate\Support\Facades\DB;

class CollaboratorController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Collaborator::query();

        $joins = [
            'sector_offices' => false,
            'sectors' => false,
        ];

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);

                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];

                    if ($field === 'search') {
                        $query->where(function ($subQuery) use ($value) {
                            foreach (Collaborator::searchableFields() as $searchableField) {
                                $subQuery->orWhere("collaborators.$searchableField", 'like', "%$value%");
                            }
                        });
                    } else {
                        if (Collaborator::isFieldFilterable($field)) {
                            $query->where("collaborators.$field", $value);
                        } elseif ($field === 'sector_id') {
                            if (!$joins['sectors']) {
                                $query->leftJoin('sectors', 'sectors.id', '=', 'collaborators.sector_id');
                                $joins['sectors'] = true;
                            }
                            $query->where('sectors.id', $value);
                        } elseif ($field === 'sector_office_id') {
                            if (!$joins['sector_offices']) {
                                $query->leftJoin('sector_offices', 'sector_offices.id', '=', 'collaborators.sector_office_id');
                                $joins['sector_offices'] = true;
                            }
                            $query->where('sector_offices.id', $value);
                        }
                    }
                }
            }
        }


        if ($request->input('order_by')) {
            $sortField = $request->input('order_by');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

            if (str_contains($sortField, '.')) {
                [$relation, $field] = explode('.', $sortField);

                if ($relation === 'sector_office') {
                    if (!$joins['sector_offices']) {
                        $query->leftJoin('sector_offices', 'sector_offices.id', '=', 'collaborators.sector_office_id');
                        $joins['sector_offices'] = true;
                    }
                    $query->orderBy("sector_offices.$field", $direction);
                } elseif ($relation === 'sector') {
                    if (!$joins['sectors']) {
                        $query->leftJoin('sectors', 'sectors.id', '=', 'collaborators.sector_id');
                        $joins['sectors'] = true;
                    }
                    $query->orderBy("sectors.$field", $direction);
                }
            } else {
                $query->orderBy("collaborators.$sortField", $direction);
            }
        }

        $query->select('collaborators.*');

        $collaborators = $query->paginate($perPage);

        return response()->json([
            'data' => CollaboratorResource::collection($collaborators),
            'pagination' => [
                'page' => $collaborators->currentPage(),
                'total_pages' => $collaborators->lastPage(),
                'per_page' => $collaborators->perPage(),
                'total' => $collaborators->total(),
            ],
            'sortable_fields' => Collaborator::sortableFields(),
            'filterable_fields' => Collaborator::filterableFields(),
        ], 200);
    }

    public function store(StoreCollaboratorRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();

            $collaborator = Collaborator::create($data);

            if (!empty($data['has_login'])) {
                $existingUser = User::where('email', $collaborator->email)->first();
    
                if ($existingUser) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Email already registered as a user.',
                    ], 422);
                }
    
                $deletedUser = User::withTrashed()->where('email', $collaborator->email)->first();
    
                if ($deletedUser) {
                    $deletedUser->update(['email' => $collaborator->email . '.deleted_' . time()]);
                }
    

                $randomPassword = Str::random(8);
                $userData = [
                    'name'     => $collaborator->name,
                    'email'    => $collaborator->email,
                    'password' => bcrypt($randomPassword),
                ];
                $user = User::create($userData);
    
                $collaborator->update(['user_id' => $user->id]);
    
                $roleAdmin = Role::where('name', 'Admin')->first();
                $user->assignRole($roleAdmin);
    
                $user->notify(new UserPassword($randomPassword));
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

            if (!empty($data['bank_details'])) {
                foreach ($data['bank_details'] as $bankData) {
                    BankDetail::updateOrCreate(
                        ['bank_detailable_id' => $collaborator->id, 'bank_detailable_type' => Collaborator::class],
                        [
                            'bank_name' => $bankData['bank_name'],
                            'agency' => $bankData['agency'],
                            'account' => $bankData['account'],
                            'status' => $bankData['status'],
                        ]
                    );
                }
            }
            if (!empty($data['pix_keys'])) {
                foreach ($data['pix_keys'] as $pixKey) {
                    PixKey::updateOrCreate(
                        ['pix_keyable_id' => $collaborator->id, 'pix_keyable_type' => Collaborator::class],
                        [
                            'type' => $pixKey['type'],
                            'key' => $pixKey['key']
                        ]
                    );
                }
            }

            $this->syncQualifications($collaborator, $data);
            DB::commit();

            return response()->json(new CollaboratorResource($collaborator), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Collaborator $collaborator)
    {
        return response()->json(new CollaboratorResource($collaborator), 200);
    }

    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    {

        try {
            DB::beginTransaction();
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
                    $collaborator->update(['file_id' => null]);
                }
            }

            if (!empty($data['has_login'])) {
                $user = $collaborator->user;
    
                if (!$user) {
                    $existingUser = User::where('email', $collaborator->email)->first();
    
                    if ($existingUser) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Email already registered as a user.',
                        ], 422);
                    }
    
                    $deletedUser = User::withTrashed()->where('email', $collaborator->email)->first();
    
                    if ($deletedUser) {
                        $deletedUser->update(['email' => $collaborator->email . '.deleted_' . time()]);
                    }
    
                    $randomPassword = Str::random(8);
                    $userData = [
                        'name'     => $collaborator->name,
                        'email'    => $collaborator->email,
                        'password' => bcrypt($randomPassword),
                    ];
                    $user = User::create($userData);
    
                    $collaborator->update(['user_id' => $user->id]);
    
                    $roleAdmin = Role::where('name', 'Admin')->first();
                    $user->assignRole($roleAdmin);
    
                    $user->notify(new UserPassword($randomPassword));
                } else {
                    if ($collaborator->email !== $user->email) {
                        $existingUser = User::where('email', $collaborator->email)->first();
    
                        if ($existingUser) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Email already registered as a user.',
                            ], 422);
                        }
    
                        $deletedUser = User::withTrashed()->where('email', $collaborator->email)->first();
    
                        if ($deletedUser) {
                            $deletedUser->update(['email' => $collaborator->email . '.deleted_' . time()]);
                        }
    
                        $user->update(['email' => $collaborator->email]);
                    }
                }
            }

            if (!empty($data['address']) && !empty($data['address']['street'])) {
                $address = $data['address'];
                Address::updateOrCreate(
                    [
                        'addressable_id' => $collaborator->id,
                        'addressable_type' => Collaborator::class
                    ],
                    [
                        'addressable_id' => $collaborator->id,
                        'addressable_type' => Collaborator::class,
                        'street' => $address['street'],
                        'district' => $address['district'],
                        'complement' => $address['complement'],
                        'city_id' => $address['city_id'],
                        'state_id' => $address['state_id'],
                        'number' => $address['number'],
                        'zip_code' => $address['zip_code'],
                        'reference' => $address['reference'],
                    ]
                );
            }
            $bankDetails = BankDetail::with('bankDetailable')
                ->where('bank_detailable_type', 'App\Models\Collaborator')
                ->where('bank_detailable_id', $collaborator->id)
                ->get();

            if (!empty($request->bank_details)) {
                foreach ($request->bank_details as $bankData) {
                    if (isset($bankData['id'])) {
                        BankDetail::updateOrCreate(
                            ['id' => $bankData['id'], 'bank_detailable_id' => $collaborator->id, 'bank_detailable_type' => Collaborator::class],
                            [
                                'bank_name' => $bankData['bank_name'],
                                'agency' => $bankData['agency'],
                                'account' => $bankData['account'],
                                'status' => $bankData['status'],
                            ]
                        );
                    } else {
                        BankDetail::create([
                            'bank_detailable_id' => $collaborator->id,
                            'bank_detailable_type' => Collaborator::class,
                            'bank_name' => $bankData['bank_name'],
                            'agency' => $bankData['agency'],
                            'account' => $bankData['account'],
                            'status' => $bankData['status'],
                        ]);
                    }
                }
            }

            if (!empty($data['removed_bank_detail_ids'])) {
                BankDetail::whereIn('id', $data['removed_bank_detail_ids'])->delete();
            }


            if ($request->has('pix_keys')) {
                foreach ($request->pix_keys as $pixKeyData) {
                    PixKey::updateOrCreate(
                        ['pix_keyable_id' => $collaborator->id, 'pix_keyable_type' => Collaborator::class],
                        ['key' => $pixKeyData['key'], 'type' => $pixKeyData['type']]
                    );
                }
            }

            if (!empty($data['removed_pix_key_ids'])) {
                PixKey::whereIn('id', $data['removed_pix_key_ids'])->delete();
            }

            $this->syncQualifications($collaborator, $data);

            DB::commit();
            return response()->json(new CollaboratorResource($collaborator), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function destroy(Collaborator $collaborator)
    {
        if ($collaborator->user) {
            $collaborator->user->delete();
        }
        $collaborator->delete();
        return response()->json([], 204);
    }

    public function syncQualifications(Collaborator $collaborator, array $data)
    {

        $availableQualifications = Qualification::all();

        $qualificationsData = [];

        foreach ($availableQualifications as $qualification) {
            $qualificationsData[$qualification->id] = ['points' => 0];
        }

        foreach ($data['qualifications'] as $qualification) {
            if (!empty($qualification['id'])) {
                $qualificationsData[$qualification['id']] = ['points' => $qualification['points']];
            }
        }

        $collaborator->qualifications()->syncWithoutDetaching($qualificationsData);
    }

    public function updateQualifications(Request $request, Collaborator $collaborator)
    {
        if (!empty($request->qualifications)) {
            foreach ($request->qualifications as $qualification) {
                $collaborator->qualifications()->syncWithoutDetaching([$qualification['id'] => ['points' => $qualification['points']]]);
            }
        }

        return response()->json(['message' => 'Qualificações atualizadas com sucesso!']);
    }
}
