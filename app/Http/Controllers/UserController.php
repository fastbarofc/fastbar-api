<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\SyncRoleUserRequest;
use App\Http\Requests\SyncPermissionUserRequest;


class UserController extends Controller
{

    public function index()
    {
        $users = User::all();
        return UserResource::collection($users);
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
        $user->syncRoles($request->roles);
        return new UserResource($user);
    }

    public function syncPermissions(SyncPermissionUserRequest $request, User $user)
    {
        $user->syncPermissions($request->permissions);
        return new UserResource($user);
    }
}
