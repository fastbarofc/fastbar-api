<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Http\Request;
use App\Models\Event;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Contract::query();

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $contracts = $query->paginate($perPage);

        return response()->json([
            'data' => ContractResource::collection($contracts),
            'pagination' => [
                'current_page' => $contracts->currentPage(),
                'total_pages' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
            'soretable_fields' => Contract::sortableFields()
        ], 200);
    }

    public function store(StoreContractRequest $request)
    {
        $data = $request->all();
        $data['version'] = Contract::where('event_id', $data['event_id'])->count();
        $data['properties'] = [];
        $data['properties_diff'] = [];

        $event = Event::find($data['event_id']);

        $mainClient = $event->client?->where('pivot.main', true)->first();

        if (empty($mainClient->email)) {
            return response()->json(['message' => 'O cliente principal não possui e-mail cadastrado.'], 400);
        }

        $properties = $event->getAttributes();

        $materials = $event->materials;
        $eventKits = $event->eventKits;
        $eventGifts = $event->eventGifts;
        $extraSales = $event->extraSales;
        $officeEvents = $event->officeEvents;

        $properties['materials'] = $materials->toArray();
        $properties['event_kits'] = $eventKits->toArray();
        $properties['event_gifts'] = $eventGifts->toArray();
        $properties['extra_sales'] = $extraSales->toArray();
        $properties['office_events'] = $officeEvents->toArray();

        $data['properties'] = $properties;

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

    public function toSign(Request $request, Contract $contract)
    {
        $data = $request->all();
        $data['signed_at'] = now();
        $data['ip'] = $request->ip();

        $contract->update($data);
        return response()->json(new ContractResource($contract), 200);
    }
}
