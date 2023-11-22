<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\PermissionResource;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return response()->json(PermissionResource::collection($permissions), 200);
    }

    public function show(Permission $permission)
    {
        return response()->json(new PermissionResource($permission), 200);
    }
}
