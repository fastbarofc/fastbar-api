<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventPaymentRequest;
use App\Http\Requests\UpdateEventPaymentRequest;
use App\Http\Resources\EventPaymentResource;
use App\Models\EventPayment;
use Illuminate\Http\Request;

class EventPaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventPayment::class, 'eventPayment');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = EventPayment::query();

        if ($request->input('filter_by')) {
            $filterable = $request->input('filter_by');
            $filterableArray = json_decode($filterable, true);
            foreach ($filterableArray as  $filter) {
                $field = array_keys($filter)[0];
                $value = $filter[$field];
                $query->filterByField($field, $value);
            }
        }

        if ($request->input('order_by')) {
            $sorteableField = $request->input('order_by');
            $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
            $query->orderByField($sorteableField, $direction);
        }

        $eventPayments = $query->with(['event', 'parthenonBankAccount'])->paginate($perPage);

        return response()->json([
            'data' => EventPaymentResource::collection($eventPayments),
            'pagination' => [
                'current_page' => $eventPayments->currentPage(),
                'total_pages' => $eventPayments->lastPage(),
                'per_page' => $eventPayments->perPage(),
                'total' => $eventPayments->total(),
            ],
            'sortable_fields' => EventPayment::sortableFields(),
            'filterable_fields' => EventPayment::filterableFields()
        ], 200);
    }

    public function store(StoreEventPaymentRequest $request)
    {
        $eventPayment = EventPayment::create($request->all());
        // Carregar os relacionamentos necessários
        $eventPayment->load(['event', 'parthenonBankAccount']);
        return response()->json(new EventPaymentResource($eventPayment), 201);
    }

    public function show(EventPayment $eventPayment)
    {
        // Carregar os relacionamentos necessários
        $eventPayment->load(['event', 'parthenonBankAccount']);
        return response()->json(new EventPaymentResource($eventPayment), 200);
    }

    public function update(UpdateEventPaymentRequest $request, EventPayment $eventPayment)
    {
        $eventPayment->update($request->all());
        // Recarregar os relacionamentos necessários após a atualização
        $eventPayment->load(['event', 'parthenonBankAccount']);
        return response()->json(new EventPaymentResource($eventPayment), 200);
    }

    public function destroy(EventPayment $eventPayment)
    {
        $eventPayment->delete();
        return response()->json([], 204);
    }
}
