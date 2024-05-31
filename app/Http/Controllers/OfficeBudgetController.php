<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeBudgetRequest;
use App\Http\Requests\UpdateOfficeBudgetRequest;
use App\Models\OfficeBudget;
use Illuminate\Http\Request;
use App\Http\Resources\OfficeBudgetResource;

class OfficeBudgetController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(OfficeBudget::class, 'officeBudget');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = OfficeBudget::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $officeBudgets = $query->paginate($perPage);

        return response()->json([
            'data' => OfficeBudgetResource::collection($officeBudgets),
            'pagination' => [
                'current_page' => $officeBudgets->currentPage(),
                'total_pages' => $officeBudgets->lastPage(),
                'per_page' => $officeBudgets->perPage(),
                'total' => $officeBudgets->total(),
            ],
            'sortable_fields' => OfficeBudget::sortableFields(),
            'filterable_fields' => OfficeBudget::filterableFields()
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfficeBudgetRequest $request)
    {
        try{
            $data = $request->all();
            $data['slug'] = slug($data['name']);
            $officeBudget = OfficeBudget::create($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(new OfficeBudgetResource($officeBudget), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(OfficeBudget $officeBudget)
    {
        return response()->json(new OfficeBudgetResource($officeBudget), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfficeBudgetRequest $request, OfficeBudget $officeBudget)
    {
        try{
            $data = $request->all();
            $data['slug'] = slug($data['name']);
            $officeBudget->update($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(new OfficeBudgetResource($officeBudget), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfficeBudget $officeBudget)
    {
        $officeBudget->delete();
        return response()->json([], 204);
    }
}
