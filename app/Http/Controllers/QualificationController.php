<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use App\Http\Resources\QualificationResource;

class QualificationController extends Controller
{

    public function index()
    {
        $qualifications = Qualification::all();
        return response()->json(QualificationResource::collection($qualifications));
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
