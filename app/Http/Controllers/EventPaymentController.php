<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventPaymentRequest;
use App\Http\Requests\UpdateEventPaymentRequest;
use App\Http\Resources\EventPaymentResource;
use App\Models\EventPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventPaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventPayment::class, 'eventPayment');
    }

    public function index(Request $request)
{
    $perPage = $request->input('per_page', 10);

    Log::info("message", ['request' => $request->all()]);

    $query = EventPayment::query();

    if ($request->input('eventId')) {
        $query->where('event_id', $request->input('eventId'));
    }

    if ($request->input('filter_by')) {
        $filterable = $request->input('filter_by');
        if ($filterable && $filterable !== '[]') {
            $filterableArray = json_decode($filterable, true);
            $filterableArray = $filterableArray[0];
            foreach ($filterableArray as  $field => $value) {
                $query->filterByField($field, $value);
            }
        }
    }

    if ($request->input('order_by')) {
        $sorteableField = $request->input('order_by');
        $direction = $request->input('direction') == 'desc' ? 'desc' : 'asc';
        $query->orderByField($sorteableField, $direction);
    }

    $eventPayments = $query->with(['event', 'parthenonBankAccount'])->paginate($perPage);

    Log::info("message", ['eventPayments' => $eventPayments]);

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
        $eventPayment->load(['event', 'parthenonBankAccount']);
        return response()->json(new EventPaymentResource($eventPayment), 201);
    }

    public function show(EventPayment $eventPayment)
    {
        $eventPayment->load(['event', 'parthenonBankAccount']);
        return response()->json(new EventPaymentResource($eventPayment), 200);
    }

    public function update(UpdateEventPaymentRequest $request, EventPayment $eventPayment)
    {
        $eventPayment->update($request->all());
        $eventPayment->load(['event', 'parthenonBankAccount']);
        return response()->json(new EventPaymentResource($eventPayment), 200);
    }

    public function destroy(EventPayment $eventPayment)
    {
        $eventPayment->delete();
        return response()->json([], 204);
    }
}
