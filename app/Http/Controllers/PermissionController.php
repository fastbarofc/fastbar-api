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

        $query = Permission::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as $field => $value) {
                $query->where($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderBy($sorteableField, $direction);
        }

        $permissions = Permission::paginate($perPage);

        return response()->json([
            'data' => PermissionResource::collection($permissions),
            'pagination' => [
                'current_page' => $permissions->currentPage(),
                'total_pages' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
            'sortable_fields' => ['name'],
            'filterable_fields' => ['name'],
        ], 200);
    }

    public function show(Permission $permission)
    {
        return response()->json(new PermissionResource($permission), 200);
    }
}
