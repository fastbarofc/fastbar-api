<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventKit;
use App\Services\FileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use mikehaertl\wkhtmlto\Pdf;
use Illuminate\Support\Arr;

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
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                $filterableArray = $filterableArray[0];
                foreach ($filterableArray as $field => $value) {
                    $query->filterByField($field, $value);
                }
            }
        }

        if ($request->input('order_by')) {
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
        $event = Event::find($data['id']);
        $collaborators = $event->officeEvents()->wherePivot('quantity', '>', 0)->withPivot('quantity')->get();
        $kits = $event->eventKits()->whereNull('event_kit_id')->get();
        $selectedMaterials = $event->materials->filter(function ($material) {
            return $material->pivot->selected == true;
        });

        $data['version'] = Contract::where('event_id', $data['id'])->count() + 1;
        $data['properties'] = [];
        $data['event_id'] = $data['id'];
        $data['client_id'] = $event->client_id;
        
        $properties = Arr::only($event->getAttributes(), ['id', 'name', 'date_time', 'duration', 'lease', 'buffet_value']);
        
        $properties['materials'] = [];
        $properties['collaborators'] = [];
        $properties['event_kits'] = [];

        $properties['client'] = $event->client
            ->select('id', 'name', 'phone_1')
            ->first()
            ->toArray();

        $properties['place'] = $event->eventPlace
            ->select('id', 'name')
            ->first()
            ->toArray();

        $properties['batches'] = $event->eventBatches()
            ->select('id', 'unit_value', 'guests')
            ->get()
            ->toArray();
        
        foreach ($selectedMaterials as $material) {
            $properties['materials'][] = [
                'id' => $material->id,
                'name' => ucfirst($material->description),
                'quantity' => $material->quantity,
            ];
        }
        
        foreach ($collaborators as $collaborator) {
            $properties['collaborators'][] = [
                'id' => $collaborator->id,
                'name' => $collaborator->description,
                'quantity' => $collaborator->pivot->quantity
            ];
        }
        
        foreach ($kits as $kit) {
            $properties['event_kits'][$kit->name] = [];
            foreach ($kit->children as $child) {
                $properties['event_kits'][$kit->name][] = [
                    $child['data']['name'],
                ];
                if (!empty($child['children'])) {
                    $kitLevel2 = EventKit::find($child['data']['id']);
                    $childrenKitLevel2 = $kitLevel2->children;
                    if(!empty($childrenKitLevel2)) {
                        foreach ($childrenKitLevel2 as $product) {
                            $productName = is_array($product['data']['name']) ? reset($product['data']['name']) : $product['data']['name'];
                            $properties['event_kits'][$kit->name][$child['data']['name']][] = $productName;
                        }
                    }
                }
            }
        }
        
        $data['properties'] = $properties;
        $data['properties_diff'] = $this->calculatePropertiesDiff($data);
        
        $contract = Contract::create($data);
        
        if (!$contract) {
            return response()->json(['error' => 'Contract creation failed'], 500);
        }
        
        
        $pdf = new Pdf();
        $fileName = 'contrato-evento-' . $event->id;
        
        $date = $contract->created_at->format('d/m/Y');
        $client = $contract->properties['client'];
        $place = $contract->properties['place'];
        
        $kits = $properties['event_kits'];
        $batches = $contract->properties['batches'];
        
        $collaborators = $contract->properties['collaborators'];
        $materials = $contract->properties['materials'];
        $event = $contract->properties;


        $viewContent = view("/pdfs/contract-pdf", compact(['event', 'date', 'client', 'place', 'kits', 'materials', 'batches', 'collaborators']));
        
        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculatePropertiesDiff(array $data)
    {
        $properties_diff = [];

        if ($data['version'] > 1) {
            $lastContract = Contract::where('event_id', $data['id'])->orderBy('version', 'desc')->first();

            $newProperties = $data['properties'];
            $oldProperties = $lastContract->properties;

            $newProperties = $this->sortArrayRecursively($newProperties);
            $oldProperties = $this->sortArrayRecursively($oldProperties);

            $addedProperties = array_diff_key($newProperties, $oldProperties);
            $removedProperties = array_diff_key($oldProperties, $newProperties);
            $modifiedProperties = [];

            foreach ($oldProperties as $key => $value) {
                if (array_key_exists($key, $newProperties) && $newProperties[$key] !== $value) {
                    $modifiedProperties[$key] = [
                        'old' => $value,
                        'new' => $newProperties[$key],
                    ];
                }
            }

            $properties_diff = [
                'added' => $addedProperties,
                'removed' => $removedProperties,
                'modified' => $modifiedProperties,
            ];
        }

        return $properties_diff;
    }

    private function sortArrayRecursively(array $array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sortArrayRecursively($value);
            }
        }

        ksort($array);
        return $array;
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
