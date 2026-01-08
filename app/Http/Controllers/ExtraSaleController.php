<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraSaleRequest;
use App\Http\Requests\UpdateExtraSaleRequest;
use App\Http\Resources\ExtraSaleResource;
use App\Models\ExtraSale;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Services\EventHistoryService;

class ExtraSaleController extends Controller
{
    public function __construct(protected EventHistoryService $historyService)
    {
        // TODO: TEMPORÁRIO: Policy desativada - descomente para reativar
        // $this->authorizeResource(ExtraSaleController::class, 'extraSale');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = ExtraSale::query();

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

        if($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $extraSales = $query->paginate($perPage);

        return response()->json([
            'data' => ExtraSaleResource::collection($extraSales),
            'pagination' => [
                'page' => $extraSales->currentPage(),
                'total_pages' => $extraSales->lastPage(),
                'per_page' => $extraSales->perPage(),
                'total' => $extraSales->total(),
            ],
            'sortable_fields' => ExtraSale::sortableFields(),
            'filterable_fields' => ExtraSale::filterableFields()
        ], 200);
    }

    public function store(StoreExtraSaleRequest $request)
    {
        $data = $request->all();
        
        $event = Event::find($data['event_id']);
        if ($event) {
            $originalExtraSales = $event->extraSales->toArray();
            
            $extraSale = ExtraSale::create($data);
            
            $event->refresh();
            $newExtraSales = $event->extraSales->toArray();
            
            $originalData = ['extra_sales' => $originalExtraSales];
            $requestData = ['extra_sales' => $newExtraSales];
            
            $this->historyService->logEventUpdate($event, $originalData, $requestData);
        } else {
            $extraSale = ExtraSale::create($data);
        }
        
        return response()->json(new ExtraSaleResource($extraSale), 201);
    }

    public function show(ExtraSale $extraSale)
    {
        return response()->json(new ExtraSaleResource($extraSale), 200);
    }

    public function update(UpdateExtraSaleRequest $request, ExtraSale $extraSale)
    {
        $event = $extraSale->event;
        
        if ($event) {
            $originalExtraSales = $event->extraSales->toArray();
        }
        
        $extraSale->update($request->all());
        
        if ($event) {
            $event->refresh();
            $newExtraSales = $event->extraSales->toArray();
            
            $originalData = ['extra_sales' => $originalExtraSales];
            $requestData = ['extra_sales' => $newExtraSales];
            
            $this->historyService->logEventUpdate($event, $originalData, $requestData);
        }
        
        return response()->json(new ExtraSaleResource($extraSale), 200);
    }

    public function destroy(ExtraSale $extraSale)
    {
        $event = $extraSale->event;
        
        if ($event) {
            $originalExtraSales = $event->extraSales->toArray();
        }
        
        $extraSale->delete();
        
        if ($event) {
            $event->refresh();
            $newExtraSales = $event->extraSales->toArray();
            
            $originalData = ['extra_sales' => $originalExtraSales];
            $requestData = ['extra_sales' => $newExtraSales];
            
            $this->historyService->logEventUpdate($event, $originalData, $requestData);
        }
        
        return response()->json([], 204);
    }

    
}
