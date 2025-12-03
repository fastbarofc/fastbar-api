<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Models\EventStatus;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(MaterialController::class, 'material');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Material::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
                    $operator = $field == 'description' ? 'like' : '=';
                    $query->filterByField(
                        field: $field,
                        value: $operator == 'like' ? "%$value%" : $value,
                        operator: $operator
                    );
                }
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $materials = $query->paginate($perPage);

        return response()->json([
            'data' => MaterialResource::collection($materials),
            'pagination' => [
                'page' => $materials->currentPage(),
                'total_pages' => $materials->lastPage(),
                'per_page' => $materials->perPage(),
                'total' => $materials->total(),
            ],
            'sortable_fields' => Material::sortableFields(),
            'filterable_fields' => Material::filterableFields()
        ], 200);
    }

    public function store(StoreMaterialRequest $request)
    {
        $material = Material::create($request->all());
        return response()->json(new MaterialResource($material), 201);
    }

    public function show(Material $material)
    {
        return response()->json(new MaterialResource($material), 200);
    }

    public function update(UpdateMaterialRequest $request, Material $material)
    {
        $material->update($request->all());
        return response()->json(new MaterialResource($material), 200);
    }

    public function destroy(Material $material)
    {
        $inactiveStatusNames = ['Concluído', 'Descartado', 'Cancelado', 'Fechou com o concorrente'];
        $inactiveStatusIds = EventStatus::whereIn('name', $inactiveStatusNames)->pluck('id')->toArray();

        $activeEvents = DB::table('event_material')
            ->join('events', 'event_material.event_id', '=', 'events.id')
            ->where('event_material.material_id', $material->id)
            ->whereNotIn('events.event_status_id', $inactiveStatusIds)
            ->whereNull('events.deleted_at')
            ->whereNull('event_material.deleted_at')
            ->where('event_material.quantity', '>', 0)
            ->pluck('events.id')
            ->toArray();

        if (!empty($activeEvents)) {
            return response()->json([
                'message' => 'Não é possível excluir o material enquanto houver eventos ativos utilizando-o.',
                'event_ids' => $activeEvents
            ], 422);
        }

        $material->delete();
        return response()->json([], 204);
    }
}
