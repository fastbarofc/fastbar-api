<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use App\Http\Resources\QualificationResource;
use Illuminate\Http\Request;

class QualificationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(QualificationController::class, 'qualification');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Qualification::query();

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

        $qualifications = $query->paginate($perPage);

        return response()->json([
            'data' => QualificationResource::collection($qualifications),
            'pagination' => [
                'current_page' => $qualifications->currentPage(),
                'total_pages' => $qualifications->lastPage(),
                'per_page' => $qualifications->perPage(),
                'total' => $qualifications->total(),
            ],
            'sortable_fields' => Qualification::sortableFields(),
            'filterable_fields' => Qualification::filterableFields()
        ], 200);
    }
    
    public function store(StoreQualificationRequest $request)
    {   
        $data = $request->all();
        $data['slug'] = slug($data['description']);
        $qualification = Qualification::create($data);
        return response()->json(new QualificationResource($qualification), 201);
    }
    
    public function show(Qualification $qualification)
    {
        return response()->json(new QualificationResource($qualification));
    }
    
    public function update(UpdateQualificationRequest $request, Qualification $qualification)
    {
        $data = $request->all();
        $data['slug'] = slug($data['description']);
        $qualification->update($data);
        return response()->json(new QualificationResource($qualification));
    }
    
    public function destroy(Qualification $qualification)
    {
        $qualification->delete();
        return response()->json(null, 204);
    }
}
