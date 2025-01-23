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

class CollaboratorController extends Controller
{

    public function index(Request $request)
{
    // Paginação
    $perPage = $request->input('per_page', 10);

    // Query inicial para o model Collaborator
    $query = Collaborator::query();

    // Variáveis para controlar os joins e evitar duplicação
    $joins = [
        'sector_offices' => false,
        'sectors' => false,
    ];

    // Tratamento de filtros
    if ($request->input('filter_by')) {
        $filterable = $request->input('filter_by');
        if ($filterable && $filterable !== '[]') {
            $filterableArray = json_decode($filterable, true);

            foreach ($filterableArray as $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];

                // Campo "search" tratado diretamente
                if ($field === 'search') {
                    $query->where(function ($subQuery) use ($value) {
                        foreach (Collaborator::searchableFields() as $searchableField) {
                            $subQuery->orWhere("collaborators.$searchableField", 'like', "%$value%");
                        }
                    });
                } else {
                    // Adiciona o prefixo do nome da tabela base ou relacionamento
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

    // Tratamento de ordenação
    if ($request->input('order_by')) {
        $sortField = $request->input('order_by');
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        if (str_contains($sortField, '.')) {
            // Ordenação por relacionamentos
            [$relation, $field] = explode('.', $sortField);

            // Relacionamentos padronizados para as tabelas no banco de dados
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
            // Ordenação por campos da tabela base
            $query->orderBy("collaborators.$sortField", $direction);
        }
    }

    // Paginação dos resultados
    $collaborators = $query->paginate($perPage);

    // Retorna a resposta com os dados e informações de paginação
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

        $data = $request->all();

        $collaborator = Collaborator::create($data);

        /* if($data['has_login'] === null) {
            $data['has_login'] = false;
        }

        if (!empty($data['has_login'])) {
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);
            $collaborator->update(['user_id' => $user->id]);
            
            $roleAdmin = Role::where('name', 'Admin')->first();
            $user->assignRole($roleAdmin);
        }  */

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
                $bank = BankDetail::updateOrCreate(
                    ['collaborator_id' => $collaborator->id],
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
                    ['collaborator_id' => $collaborator->id],
                    [
                        'type' => $pixKey['type'],
                        'key' => $pixKey['key']
                    ]
                );
            }
        }

        $this->syncQualifications($collaborator, $data);

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
                $collaborator->update(['file_id' => null]);
            }
        }

        if (!empty($data['has_login'])) {
            /* $userData = [
                'name' => $data['name'],
            ];
    
            
            if ($data['email'] !== $collaborator->email) {
                $userData['email'] = $data['email'];
            }

            if (!empty($data['password']) && !Hash::check($data['password'], $collaborator->user->password)) {
                $userData['password'] = bcrypt($data['password']);
            }
    
            $user = User::updateOrCreate(['id' => $collaborator->user_id], $userData);
            $collaborator->update(['user_id' => $user->id]);
    
            $roleAdmin = Role::where('name', 'Admin')->first();
    
            if (isset($data['active'])) {
                if ($data['active']) {
                    $user->assignRole($roleAdmin);
                } else {
                    $user->removeRole($roleAdmin);
                }
            } */
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

        if (!empty($data['bank_details'])) {
            foreach ($data['bank_details'] as $bankData) {
                if (isset($bankData['id'])) {
                    $bank = BankDetail::updateOrCreate(
                        ['id' => $bankData['id'], 'collaborator_id' => $collaborator->id],
                        [
                            'bank_name' => $bankData['bank_name'],
                            'agency' => $bankData['agency'],
                            'account' => $bankData['account'],
                            'status' => $bankData['status'],
                        ]
                    );
                } else {
                    $bank = BankDetail::create([
                        'collaborator_id' => $collaborator->id,
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

        if (!empty($data['pix_keys'])) {
            foreach ($data['pix_keys'] as $pixKey) {
                if (isset($pixKey['id'])) {
                    PixKey::updateOrCreate(
                        ['id' => $pixKey['id'], 'collaborator_id' => $collaborator->id],
                        [
                            'type' => $pixKey['type'],
                            'key' => $pixKey['key']
                        ]
                    );
                } else {
                    PixKey::create([
                        'collaborator_id' => $collaborator->id,
                        'type' => $pixKey['type'],
                        'key' => $pixKey['key']
                    ]);
                }
            }
        }

        if (!empty($data['removed_pix_key_ids'])) {
            PixKey::whereIn('id', $data['removed_pix_key_ids'])->delete();
        }

        $this->syncQualifications($collaborator, $data);

        return response()->json(new CollaboratorResource($collaborator), 200);
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
