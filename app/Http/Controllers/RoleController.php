<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use App\Http\Requests\SyncPermissionUserRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Role::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $query->where($field, 'like', "%$value%");
                }
            }
        }

        if($request->input('order_by')) {
            if ($request->input('direction') == 'desc') {
                $query->orderByDesc('name');
            } else {
                $query->orderBy('name');
            }
        }

        $roles = $query->paginate($perPage);

        return response()->json([
            'data' => RoleResource::collection($roles),
            'pagination' => [
                'page' => $roles->currentPage(),
                'total_pages' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
            'sortable_fields' => ['name'],
            'filterable_fields' => ['name'],
        ], 200);
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

    public function permissions(Role $role)
    {
        $permissions = $role->permissions;
        return response()->json([
            'data' => PermissionResource::collection($permissions)
        ], 200);
    }

    public function syncPermissions(SyncPermissionUserRequest $request, Role $role)
    {
        $role->syncPermissions($request->permissions);
        return response()->json(new RoleResource($role), 200);
    }
}
