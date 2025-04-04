<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ReceivableResource;
use App\Http\Requests\StoreReceivableRequest;
use App\Http\Requests\UpdateReceivableRequest;
use App\Models\Receivable;

class ReceivableController extends Controller
{

    public function index(Request $request)
    {
        $receivables = Receivable::with(['paymentMethod', 'receivableable'])->paginate(10);
        return response()->json(ReceivableResource::collection($receivables));
    }

    public function store(StoreReceivableRequest $request)
    {
        $receivable = Receivable::create($request->validated());
        return response()->json(new ReceivableResource($receivable), 201);
    }

    public function show(Receivable $receivable)
    {
        return response()->json(new ReceivableResource($receivable->load(['paymentMethod', 'receivableable'])));
    }

    public function update(UpdateReceivableRequest $request, Receivable $receivable)
    {
        $receivable->update($request->validated());
        return response()->json(new ReceivableResource($receivable));
    }

    public function destroy(Receivable $receivable)
    {
        $receivable->delete();
        return response()->json(['message' => 'Receivable deleted successfully'], 200);
    }
}
