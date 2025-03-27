<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
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
        $this->authorizeResource(UserController::class, 'user');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = User::query();

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

    public function destroy(User $user)
    {
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

public function changePassword(Request $request) {
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
