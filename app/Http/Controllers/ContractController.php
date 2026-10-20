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
use mikehaertl\wkhtmlto\Pdf;
use Illuminate\Support\Arr;
use App\Models\Client;
use App\Models\EventPlace;

class ContractController extends Controller
{

    public function __construct()
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(ContractController::class, 'contract');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Contract::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            if ($filterable && $filterable !== '[]') {
                $filterableArray = json_decode($filterable, true);
                foreach ($filterableArray as $filter) {
                    $field = array_keys($filter)[0];
                    $value = $filter[$field];
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
                'page' => $contracts->currentPage(),
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
        $event = Event::find($request->id);

        $properties = Arr::only($event->getAttributes(), ['id', 'name', 'date', 'time', 'duration', 'lease', 'buffet_value', 'event_guests', 'sub_total', 'total_value', 'payment_method']);

        $properties['date'] = $event->date;
        if (empty($event->time)) {
            $properties['time'] = '';
        } else {
            $properties['time'] = $event->time;
        }

        $client = Client::with('address')
        ->select('id', 'name', 'phone_1', 'cpf', 'rg')
        ->find($event->client_id);

        $properties['client'] = $client 
        ? $client->toArray()
        : [
            'id' => null, 
            'name' => null, 
            'phone_1' => null,
            'cpf' => null,
            'rg' => null,
            'address' => [
                'street' => null,
                'number' => null,
                'complement' => null,
                'district' => null,
                'zip_code' => null,
            ]
        ];

        $place = EventPlace::select('id', 'name')->find($event->event_place_id);

        $properties['place'] = $place
        ? $place->toArray()
            : ['id' => null, 'name' => null];

        $properties['batches'] = $event->eventBatches()
            ->select('id', 'unit_value', 'guests')
            ->get()
            ->toArray() ?? [];

        $properties['materials'] = $this->getContractMaterials($event) ?? [];
        $properties['collaborators'] = $this->getContractCollaborators($event) ?? [];
        $properties['event_kits'] = $this->getContractKits($event) ?? [];

        $properties['payment_plan'] = [
            'payment_method' => $event->paymentPlan?->paymentMethod?->name ?? null,
            'total_amount' => $event->paymentPlan?->total_amount ?? null,
            'down_payment' => $event->paymentPlan?->down_payment ?? null,
            'num_installments' => $event->paymentPlan?->num_installments ?? null,
            'installment_amount' => $event->paymentPlan?->installment_amount ?? null,
            'due_day' => $event->paymentPlan?->due_day ?? null,
        ];

        $contract = Contract::recordVersion($event, $properties, $event->contract_body ?? '');

        if (!$contract) {
            return response()->json(['error' => 'Contract creation failed'], 500);
        }

        $pdf = new Pdf();
        $fileName = 'contrato-evento-' . $event->id;

        $viewContent = view("/pdfs/contract-pdf", compact(['contract']));

        try {
            $pdfUrl = FileService::generatePdf($viewContent, $fileName);
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getContractMaterials(Event $event)
    {
        $selectedMaterials = $event->materials->filter(function ($material) {
            return $material->pivot->selected == true;
        });

        $contractMaterials = [];

        foreach ($selectedMaterials as $material) {
            $contractMaterials[] = [
                'id' => $material->id,
                'name' => ucfirst($material->description),
                'quantity' => $material->quantity,
            ];
        }

        return $contractMaterials;
    }

    public function getContractCollaborators(Event $event)
    {
        $collaborators = $event->officeEvents()->wherePivot('quantity', '>', 0)->withPivot('quantity')->get();

        $contractCollaborators = [];

        foreach ($collaborators as $collaborator) {
            $contractCollaborators[] = [
                'id' => $collaborator->id,
                'name' => $collaborator->description,
                'quantity' => $collaborator->pivot->quantity
            ];
        }

        return $contractCollaborators;
    }

    public function getContractKits(Event $event)
    {
        $kits = $event->eventKits()->whereNull('event_kit_id')->get();

        $contractKits = [];

        foreach ($kits as $kit) {
            $kitData = [
                'id' => $kit->id,
                'name' => $kit->name,
                'type' => 'kit',
                'children' => []
            ];

            foreach ($kit->children as $child) {
                $kitData['children'][] = $this->processKitChildren($child);
            }

            $contractKits[] = $kitData;
        }

        return $contractKits;
    }

    public function processKitChildren($kit)
    {
        if (empty($kit)) {
            return [];
        }

        if (!empty($kit['children'])) {
            $kit = EventKit::find($kit['data']['id']);

            $result = [
                'id' => $kit->id,
                'name' => $kit->name,
                'type' => 'kit',
                'children' => $kit->children->map(function ($child) {
                    return $this->processKitChildren($child);
                })->toArray()
            ];

            return $result;
        }

        return [
            'id' => $kit['data']['id'],
            'name' => $kit['data']['name'],
            'type' => 'product',
        ];
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

        $properties['payment_plan'] = [
            'payment_method' => $event->paymentPlan?->paymentMethod?->name ?? null,
            'total_amount' => $event->paymentPlan?->total_amount ?? null,
            'down_payment' => $event->paymentPlan?->down_payment ?? null,
            'num_installments' => $event->paymentPlan?->num_installments ?? null,
            'installment_amount' => $event->paymentPlan?->installment_amount ?? null,
            'due_day' => $event->paymentPlan?->due_day ?? null,
        ];

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

    public function checkSignedContract($eventId) {
        $hasSignedContract = Contract::where('event_id', $eventId)
            ->whereNotNull('signed_at')
            ->exists();

        return response()->json(['hasSignedContract' => $hasSignedContract]);
    }
}
