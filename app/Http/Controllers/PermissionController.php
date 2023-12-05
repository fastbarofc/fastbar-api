<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\PermissionResource;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $permissions = Permission::paginate($perPage);

        return response()->json([
            'data' => PermissionResource::collection($permissions),
            'pagination' => [
                'current_page' => $permissions->currentPage(),
                'total_pages' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
        ], 200);
    }

    public function show(Permission $permission)
    {
        return response()->json(new PermissionResource($permission), 200);
    }
}
