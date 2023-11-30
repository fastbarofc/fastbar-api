<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::all();
        return response()->json(ContractResource::collection($contracts), 200);
    }

    public function store(StoreContractRequest $request)
    {
        $data = $request->all();
        $data['version'] = Contract::where('event_id', $data['event_id'])->count();
        $data['properties'] = [];
        $data['properties_diff'] = [];

        $contract = Contract::create($data);
        return response()->json(new ContractResource($contract), 201);
    }

    public function show(Contract $contract)
    {
        return response()->json(new ContractResource($contract), 200);
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $data = $request->all();

        $contract->update($data);
        return response()->json(new ContractResource($contract), 200);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return response()->json([], 204);
    }
}
