<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Http\Resources\UserResource;
use App\Http\Requests\SyncRoleUserRequest;
use App\Http\Requests\SyncPermissionUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdateProfileRequest;

class UserController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(UserController::class, 'user');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = User::query()->with(['roles', 'collaborator']);

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->filterByField($field, $value);
                }
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'pagination' => [
                'page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'sortable_fields' => User::sortableFields(),
            'filterable_fields' => User::filterableFields()
        ], 200);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->all());
        return new UserResource($user);
    }

    /**
     * Colaboradores que ainda não têm acesso ao sistema (para a tela Gestão de usuários > Adicionar).
     * Regra: todo usuário nasce de um colaborador já cadastrado.
     */
    public function availableCollaborators(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = Collaborator::query()
            ->with('sectorOffice')
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhereDoesntHave('user'); // usuário excluído: colaborador volta a ficar disponível
            })
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        return response()->json([
            'data' => $query->limit(50)->get()->map(fn (Collaborator $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'office' => $c->sectorOffice?->name,
            ]),
        ]);
    }

    /** Cria o acesso de um colaborador já cadastrado (Gestão de usuários > Adicionar). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'collaborator_id' => ['required', 'integer', 'exists:collaborators,id'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ], [
            'collaborator_id.required' => 'Escolha o colaborador que vai receber o acesso.',
            'collaborator_id.exists' => 'Colaborador não encontrado. Cadastre a pessoa em Cadastros > Colaboradores primeiro.',
            'email.required' => 'Informe o e-mail de acesso.',
            'email.email' => 'O e-mail de acesso não é válido.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $collaborator = Collaborator::findOrFail($data['collaborator_id']);
        if ($collaborator->user_id && $collaborator->user) {
            return response()->json(['message' => "{$collaborator->name} já tem acesso ao sistema."], 422);
        }

        return DB::transaction(function () use ($data, $collaborator) {
            // Um usuário excluído com o mesmo e-mail não pode travar o novo acesso.
            User::onlyTrashed()->where('email', $data['email'])->each(function (User $old) {
                $old->update(['email' => $old->email . '.deleted_' . time()]);
            });

            $user = User::create([
                'name' => $collaborator->name,
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'is_employee' => true,
                'active' => true,
            ]);

            $collaborator->update([
                'user_id' => $user->id,
                'has_login' => true,
                'email' => $collaborator->email ?: $data['email'],
            ]);

            if (!empty($data['role_id'])) {
                $user->assignRole(Role::findOrFail($data['role_id']));
            }

            return (new UserResource($user->load(['roles', 'collaborator'])))->response()->setStatusCode(201);
        });
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()?->id === $user->id) {
            return response()->json(['message' => 'Você não pode excluir o seu próprio usuário.'], 422);
        }

        // O colaborador continua cadastrado, só perde o acesso (e pode receber outro depois).
        Collaborator::where('user_id', $user->id)->update(['user_id' => null, 'has_login' => false]);

        $user->delete();
        return response([], 204);
    }

    public function syncRoles(SyncRoleUserRequest $request, User $user)
    {
        $this->authorize('syncRoles', $user);
        
        $user->syncRoles($request->roles);
        return new UserResource($user);
    }
    
    public function syncPermissions(SyncPermissionUserRequest $request, User $user)
    {
        $this->authorize('syncPermissions', $user);

        $user->syncPermissions($request->permissions);
        return new UserResource($user);
    }

    /** Perfil e permissões da pessoa (tela Gestão de usuários). */
    public function access(User $user)
    {
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'role_id' => $user->roles->first()?->id,
            'custom_permissions' => (bool) $user->custom_permissions,
            'role_permission_ids' => $user->getPermissionsViaRoles()->pluck('id')->values(),
            'permission_ids' => $user->effectivePermissions()->pluck('id')->values(),
        ]);
    }

    /**
     * Troca o perfil da pessoa e, se pedido, grava permissões só dela.
     * Sem personalização, a pessoa volta a seguir as permissões do perfil.
     */
    public function updateAccess(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'custom_permissions' => ['required', 'boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $user->syncRoles(!empty($data['role_id']) ? [Role::findOrFail($data['role_id'])] : []);
        $user->custom_permissions = $data['custom_permissions'];
        $user->save();
        $user->syncPermissions($data['custom_permissions'] ? Permission::whereIn('id', $data['permissions'] ?? [])->get() : []);

        return $this->access($user->fresh());
    }

    public function getClientIdByUserId($id)
{
    $user = User::find($id);

    if ($user && $user->client) {
        return $user->client->id;
    }

    return null; 
}

public function updateProfile(UpdateProfileRequest $request)
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Usuário não encontrado'], 404);
    }

    $user->update(['email' => $request->email]);

    if ($user->collaborator) {
        $user->collaborator->update([
            'email' => $request->email,
        ]);
    }

    return response()->json([
        'message' => 'Perfil atualizado com sucesso!',
        'user' => new UserResource($user),
    ], 200);
}

public function changePassword(Request $request)
{    
    /** @var \App\Models\User $user */
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'A senha fornecida está incorreta.'], 422);
    }

    $user->update(['password' => bcrypt($request->new_password)]);

    return response()->json(['message' => 'Password changed successfully'], 200);
}

}
