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
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Role::query();

        if ($request->input('filter_by')) {
            $filterableArray = $request->input('filter_by');
            foreach ($filterableArray as $field => $value) {
                $query->where($field, $value);
            }
        }

        if ($request->input('filter_by')) {
            $filterableArray = $request->input('filter_by');
            foreach ($filterableArray as $field => $value) {
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderBy($sorteableField, $direction);
        }

        $roles = Role::paginate($perPage);

        return response()->json([
            'data' => RoleResource::collection($roles),
            'pagination' => [
                'current_page' => $roles->currentPage(),
                'total_pages' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
            'sortable_fields' => ['name']
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

    public function syncPermissions(SyncPermissionUserRequest $request, Role $role)
    {
        $role->syncPermissions($request->permissions);
        return response()->json(new RoleResource($role), 200);
    }
}
