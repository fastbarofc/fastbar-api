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

    public function __construct()
    {
        $this->authorizeResource(ContractController::class, 'contract');

    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Contract::query();

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

        $contracts = $query->paginate($perPage);

        return response()->json([
            'data' => ContractResource::collection($contracts),
            'pagination' => [
                'current_page' => $contracts->currentPage(),
                'total_pages' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ],
            'sortable_fields' => Contract::sortableFields(),
            'filterable_fields' => Contract::filterableFields()
        ], 200);
    }

    public function store(StoreContractRequest $request)
    {
        $data = $request->all();
        $data['version'] = Contract::where('event_id', $data['event_id'])->count();
        $data['properties'] = [];
        $data['properties_diff'] = [];

        $event = Event::find($data['event_id']);

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
        $newData = $request->all();

        $event = $contract->event;

        $newData['version'] = Contract::where('event_id', $event->id)->count() + 1;
        $newData['properties'] = [];
        $newData['properties_diff'] = [];

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

        $newData['properties'] = $properties;

        $oldData = $contract->toArray();

        $propertiesDiff = [];

        foreach ($newData['properties'] as $key => $value) {
            if ($key == 'updated_at') {
                continue;
            }

            if ($oldData['properties'][$key] != $newData['properties'][$key]) {
                $propertiesDiff[$key] = [
                    'old' => $oldData['properties'][$key],
                    'new' => $newData['properties'][$key],
                    'operation' => empty($oldData['properties'][$key]) ? 'add' : (empty($newData['properties'][$key]) ? 'remove' : 'update')
                ];
            }
        }

        if (empty($propertiesDiff)) {
            return response()->json(['message' => 'Nenhuma alteração foi realizada no evento.'], 400);
        }

        $newData['properties_diff'] = $propertiesDiff;

        $contract->update($newData);
        return response()->json(new ContractResource($contract), 200);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return response()->json([], 204);
    }

    public function toSign(Request $request, Contract $contract)
    {
        $this->authorize('toSign', $contract);
        
        $data = $request->all();
        $data['signed_at'] = now();
        $data['ip'] = $request->ip();

        $contract->update($data);
        return response()->json(new ContractResource($contract), 200);
    }
}
