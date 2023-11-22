<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlockedDateRequest;
use App\Http\Requests\UpdateBlockedDateRequest;
use App\Models\BlockedDate;
use App\Http\Resources\BlockedDateResource;

class BlockedDateController extends Controller
{
    
    public function index()
    {
        $blockedDates = BlockedDate::all();
        return response()->json(BlockedDateResource::collection($blockedDates), 200);
    }
    
    public function store(StoreBlockedDateRequest $request)
    {
        $data = $request->all();
        $blockedDate = BlockedDate::create($data);
        return response()->json(new BlockedDateResource($blockedDate), 201);
    }
    
    public function show(BlockedDate $blockedDate)
    {
        return response()->json(new BlockedDateResource($blockedDate), 200);
    }
    
    public function update(UpdateBlockedDateRequest $request, BlockedDate $blockedDate)
    {
        $data = $request->all();
        $blockedDate->update($data);
        return response()->json(new BlockedDateResource($blockedDate), 200);
    }
    
    public function destroy(BlockedDate $blockedDate)
    {
        $blockedDate->delete();
        return response()->json(null, 204);
    }
}
