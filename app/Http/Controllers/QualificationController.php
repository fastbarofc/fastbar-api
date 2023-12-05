<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use App\Http\Resources\QualificationResource;
use Illuminate\Http\Request;

class QualificationController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $qualifications = Qualification::paginate($perPage);

        return response()->json([
            'data' => QualificationResource::collection($qualifications),
            'pagination' => [
                'current_page' => $qualifications->currentPage(),
                'total_pages' => $qualifications->lastPage(),
                'per_page' => $qualifications->perPage(),
                'total' => $qualifications->total(),
            ],
        ], 200);
    }
    
    public function store(StoreQualificationRequest $request)
    {
        $qualification = Qualification::create($request->validated());
        return response()->json(new QualificationResource($qualification), 201);
    }
    
    public function show(Qualification $qualification)
    {
        return response()->json(new QualificationResource($qualification));
    }
    
    public function update(UpdateQualificationRequest $request, Qualification $qualification)
    {
        $qualification->update($request->validated());
        return response()->json(new QualificationResource($qualification));
    }
    
    public function destroy(Qualification $qualification)
    {
        $qualification->delete();
        return response()->json(null, 204);
    }
}
