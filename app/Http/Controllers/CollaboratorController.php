<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollaboratorRequest;
use App\Http\Requests\UpdateCollaboratorRequest;
use App\Http\Requests\ChangeCollaboratorPasswordRequest;
use App\Http\Resources\CollaboratorResource;
use Spatie\Permission\Models\Role;
use App\Models\Collaborator;
use App\Models\CollaboratorImport;
use App\Models\Company;
use App\Models\User;
use App\Models\File;
use App\Models\Address;
use App\Models\BankDetail;
use App\Models\DataBank;
use App\Models\PixKey;
use App\Services\FileService;
use App\Services\CollaboratorImportService;
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

        $query = Collaborator::query()->with('companies');

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
                            if (ctype_digit(trim((string) $value))) {
                                $subQuery->orWhere('collaborators.id', (int) trim($value));
                            }
                        });
                    } elseif ($field === 'company_id') {
                        $query->whereHas('companies', fn ($q) => $q->where('companies.id', $value));
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

        // Ordem estável: o id desempata, para nenhuma linha sumir ou repetir entre páginas.
        $query->orderBy($query->getModel()->getTable() . ".id");
        $collaborators = $query->paginate($perPage);

        $collaboratorCompanies = Company::where('internal_name', 'Fastbar')->whereNull('deleted_at')->get(['id', 'internal_name']);

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
            'collaborator_companies' => $collaboratorCompanies,
        ], 200);
    }

    public function store(StoreCollaboratorRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->all();

            $companyIds = $data['company_ids'] ?? [];
            unset($data['company_ids']);
            if (empty($companyIds) && ($defaultCompanyId = Company::defaultId())) {
                $companyIds = [$defaultCompanyId];
            }

            $collaborator = Collaborator::create($data);
            $this->applyBiometricConsent($collaborator, $request);

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
                    'is_employee' => true,
                ];
                $user = User::create($userData);
    
                $collaborator->update(['user_id' => $user->id]);
    
                $role = Role::findOrFail($data['role_id']);
                $user->assignRole($role);
    
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
                            'account_type' => $bankData['account_type'] ?? null,
                            'operation' => $bankData['operation'] ?? null,
                            'account_holder' => $bankData['account_holder'] ?? null,
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

            if (!empty($data['goals'])) {
                foreach ($data['goals'] as $goal) {
                    $collaborator->goals()->updateOrCreate(
                        ['id' => $goal['id']],
                        ['type' => $goal['type'],
                        'start_date' => $goal['start_date'],
                        'end_date' => $goal['end_date'],
                        'expected_contracts' => $goal['expected_contracts']
                        ]
                    );
                }
            }

            $this->syncQualifications($collaborator, $data);

            $collaborator->companies()->sync($companyIds);
            DB::commit();

            $collaborator->load('companies');
            return response()->json(new CollaboratorResource($collaborator), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Aceite do uso da foto para confirmar presença nos eventos (LGPD). Guarda quando e quem marcou. */
    private function applyBiometricConsent(Collaborator $collaborator, Request $request): void
    {
        if (!$request->has('biometric_consent')) {
            return;
        }
        $consent = $request->boolean('biometric_consent');
        if ($consent && !$collaborator->biometric_consent_at) {
            $collaborator->update(['biometric_consent_at' => now(), 'biometric_consent_by' => $request->user()?->id]);
        } elseif (!$consent && $collaborator->biometric_consent_at) {
            $collaborator->update(['biometric_consent_at' => null, 'biometric_consent_by' => null]);
        }
    }

    public function show(Collaborator $collaborator)
    {
        $collaborator->load('companies');
        return response()->json(new CollaboratorResource($collaborator), 200);
    }

    public function update(UpdateCollaboratorRequest $request, Collaborator $collaborator)
    {

        try {
            DB::beginTransaction();
            $data = $request->except('photo');

            $companyIds = $data['company_ids'] ?? null;
            unset($data['company_ids']);

            $collaborator->update($data);
            $this->applyBiometricConsent($collaborator, $request);
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
                        'is_employee' => true,
                    ];
                    $user = User::create($userData);
    
                    $collaborator->update(['user_id' => $user->id]);
    
                    $role = Role::findOrFail($data['role_id']);
                    $user->assignRole($role);
    
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

                    if (!empty($data['role_id'])) {
                        $user->syncRoles([]);
                        $role = Role::findOrFail($data['role_id']);
                        $user->assignRole($role);
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
                            'account_type' => $bankData['account_type'] ?? null,
                            'operation' => $bankData['operation'] ?? null,
                            'account_holder' => $bankData['account_holder'] ?? null,
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
                            'account_type' => $bankData['account_type'] ?? null,
                            'operation' => $bankData['operation'] ?? null,
                            'account_holder' => $bankData['account_holder'] ?? null,
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

            if ($request->has('goals')) {
                foreach ($request->goals as $goalData) {
                    $collaborator->goals()->updateOrCreate(
                        ['id' => $goalData['id']],
                        ['type' => $goalData['type'],
                        'start_date' => $goalData['start_date'],
                        'end_date' => $goalData['end_date'],
                        'expected_contracts' => $goalData['expected_contracts']
                        ]
                    );
                }
            }

            if (!empty($data['removed_goal_ids'])) {
                $collaborator->goals()->whereIn('id', $data['removed_goal_ids'])->delete();
            }

            $this->syncQualifications($collaborator, $data);

            if (!empty($companyIds)) {
                $collaborator->companies()->sync($companyIds);
            } elseif (!$collaborator->companies()->exists() && ($defaultCompanyId = Company::defaultId())) {
                $collaborator->companies()->sync([$defaultCompanyId]);
            }

            DB::commit();
            $collaborator->load('companies');
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

        foreach (($data['qualifications'] ?? []) as $qualification) {
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

    public function changePassword(ChangeCollaboratorPasswordRequest $request, Collaborator $collaborator)
    {
        try {
            if (!$collaborator->user) {
                return response()->json([
                    'message' => 'Este colaborador não possui um usuário associado.',
                ], 422);
            }

            $collaborator->user->update([
                'password' => bcrypt($request->password),
            ]);

            return response()->json([
                'message' => 'Senha alterada com sucesso!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa a planilha do formulário de cadastro. Com preview=true só devolve o resumo.
     * Ao gravar, guarda o arquivo enviado no histórico de importações.
     */
    public function import(Request $request, CollaboratorImportService $service)
    {
        $rows = $this->importRows($request);
        if (!$rows) {
            return response()->json(['message' => 'A planilha não tem linhas para importar.'], 422);
        }
        $preview = $request->boolean('preview');

        @set_time_limit(600);

        try {
            $summary = $service->import($rows, !$preview);
        } catch (\Throwable $e) {
            Log::error('Erro ao importar colaboradores: ' . $e->getMessage());
            return response()->json(['message' => 'Não foi possível importar a planilha: ' . $e->getMessage()], 422);
        }

        if (!$preview) {
            $copy = $summary;
            unset($copy['sample']);
            $this->archiveImport($request, 'cadastro', $copy);
        }

        return response()->json($summary);
    }

    /**
     * Lista de carteira assinada: ajusta quem é CLT e quem é Freelancer. Com preview=true só mostra.
     */
    public function importClt(Request $request, CollaboratorImportService $service)
    {
        $rows = $this->importRows($request);
        if (!$rows) {
            return response()->json(['message' => 'A lista não tem linhas para importar.'], 422);
        }
        $preview = $request->boolean('preview');

        $result = $service->syncClt($rows, !$preview);

        if (!$preview) {
            $this->archiveImport($request, 'clt', [
                'in_list' => $result['in_list'],
                'become_clt' => count($result['become_clt']),
                'leave_clt' => count($result['leave_clt']),
                'new_clt' => count($result['new_clt']),
                'become_clt_names' => $result['become_clt'],
                'leave_clt_names' => $result['leave_clt'],
                'new_clt_names' => $result['new_clt'],
            ]);
        }

        return response()->json($result);
    }

    /** Histórico de importações (mais recentes primeiro) e a data da última lista CLT. */
    public function imports()
    {
        $imports = CollaboratorImport::with('user:id,name')->latest()->limit(100)->get();
        $lastClt = $imports->firstWhere('type', 'clt');
        $lastCadastro = $imports->firstWhere('type', 'cadastro');

        return response()->json([
            'data' => $imports->map(fn ($i) => [
                'id' => $i->id,
                'type' => $i->type,
                'file_name' => $i->file_name,
                'has_file' => (bool) $i->file_path,
                'user' => $i->user?->name,
                'summary' => $i->summary,
                'created_at' => $i->created_at?->toIso8601String(),
            ]),
            'last_clt' => $lastClt ? ['created_at' => $lastClt->created_at?->toIso8601String(), 'user' => $lastClt->user?->name] : null,
            'last_cadastro' => $lastCadastro ? ['created_at' => $lastCadastro->created_at?->toIso8601String(), 'user' => $lastCadastro->user?->name] : null,
        ]);
    }

    /** Baixa a planilha guardada de uma importação. */
    public function downloadImport(CollaboratorImport $collaboratorImport)
    {
        if (!$collaboratorImport->file_path || !Storage::disk('local')->exists($collaboratorImport->file_path)) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        return Storage::disk('local')->download(
            $collaboratorImport->file_path,
            $collaboratorImport->file_name ?: basename($collaboratorImport->file_path)
        );
    }

    /** Linhas da planilha: vêm como lista JSON ou como texto JSON (quando o arquivo vai junto). */
    private function importRows(Request $request): array
    {
        $rows = $request->input('rows');
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    private function archiveImport(Request $request, string $type, array $summary): void
    {
        $path = null;
        $name = null;
        $file = $request->file('file');
        if ($file && $file->isValid()) {
            $name = $file->getClientOriginalName();
            $path = $file->storeAs(
                'colaboradores-importacoes',
                now()->format('Ymd-His') . '-' . $type . '-' . Str::slug(pathinfo($name, PATHINFO_FILENAME)) . '.' . ($file->getClientOriginalExtension() ?: 'xlsx'),
                'local'
            );
        }

        CollaboratorImport::create([
            'type' => $type,
            'file_name' => $name,
            'file_path' => $path,
            'user_id' => $request->user()?->id,
            'summary' => $summary,
        ]);
    }
}
