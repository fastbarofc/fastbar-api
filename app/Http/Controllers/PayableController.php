<?php

namespace App\Http\Controllers;

use App\Http\Resources\PayableResource;
use App\Http\Requests\StorePayableRequest;
use App\Http\Requests\UpdatePayableRequest;
use App\Models\Payable;
use Illuminate\Http\Request;

class PayableController extends Controller
{

    public function index(Request $request)
    {
        $payables = Payable::with(['financialOperation', 'payableable'])->paginate(10);
        return response()->json(PayableResource::collection($payables));
    }

    public function store(StorePayableRequest $request)
    {
        $payable = Payable::create($request->validated());
        return response()->json(new PayableResource($payable), 201);
    }

    public function show(Payable $payable)
    {
        return response()->json(new PayableResource($payable->load(['financialOperation', 'payableable'])));
    }

    public function update(UpdatePayableRequest $request, Payable $payable)
    {
        $payable->update($request->validated());
        return response()->json(new PayableResource($payable));
    }

    public function destroy(string $id)
    {
        $payable->delete();
        return response()->json(['message' => 'Payable deleted successfully'], 200);
    }
}
