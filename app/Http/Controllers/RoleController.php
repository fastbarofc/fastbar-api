<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Resources\RoleResource;
use App\Http\Requests\SyncPermissionUserRequest;
use App\Http\Requests\UpdateRoleRequest;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return response()->json(RoleResource::collection($roles), 200);
    }

    public function show(Role $role)
    {
        return response()->json(new RoleResource($role), 200);
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->all();
        $data['slug'] = slug($data['name']);
        $role = Role::create($data);
        return response()->json(new RoleResource($role), 201);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->all();
        $data['slug'] = slug($data['name']);
        $role->update($data);
        return response()->json(new RoleResource($role), 200);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response([], 204);
    }

    public function syncPermissions(SyncPermissionUserRequest $request, Role $role)
    {
        $role->syncPermissions($request->permissions);
        return response()->json(new RoleResource($role), 200);
    }
}
