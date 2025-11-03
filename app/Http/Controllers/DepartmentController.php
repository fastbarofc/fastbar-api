<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(Department::class, 'department');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 30);

        $query = Department::query();

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

        if ($request->input('order_by')) {
            $sortableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sortableField, $direction);
        }

        $departments = $query->paginate($perPage);

        return response()->json([
            'data' => DepartmentResource::collection($departments),
            'pagination' => [
                'page' => $departments->currentPage(),
                'total_pages' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
            'sortable_fields' => Department::sortableFields(),
            'filterable_fields' => Department::filterableFields()
        ], 200);
    }

    public function store(StoreDepartmentRequest $request)
    {
        try {
            DB::beginTransaction();

            $department = Department::create([
                'name' => $request->name,
            ]);

            DB::commit();

            return response()->json(new DepartmentResource($department), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao criar departamento.'], 500);
        }
    }

    public function show(Department $department)
    {
        return response()->json(new DepartmentResource($department), 200);
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        try {
            DB::beginTransaction();

            $updateData = array_filter([
                'name' => $request->name,
            ], function ($value) {
                return $value !== null;
            });

            $department->update($updateData);

            DB::commit();

            return response()->json(new DepartmentResource($department), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar departamento.'], 500);
        }
    }

    public function destroy(Department $department)
    {
        try {
            DB::beginTransaction();

            $department->delete();

            DB::commit();

            return response()->json(['message' => 'Departamento excluído com sucesso.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao excluir departamento.'], 500);
        }
    }
}

