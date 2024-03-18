<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PaymentMethodController::class, 'paymentMethod');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = PaymentMethod::query();

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

        $paymentMethods = $query->paginate($perPage);

        return response()->json([
            'data' => PaymentMethodResource::collection($paymentMethods),
            'pagination' => [
                'current_page' => $paymentMethods->currentPage(),
                'total_pages' => $paymentMethods->lastPage(),
                'per_page' => $paymentMethods->perPage(),
                'total' => $paymentMethods->total(),
            ],
            'sortable_fields' => PaymentMethod::sortableFields(),
            'filterable_fields' => PaymentMethod::filterableFields()
        ], 200);
    }
    
    public function store(StorePaymentMethodRequest $request)
    {
        $paymentMethod = PaymentMethod::create($request->validated());
        return response()->json(new PaymentMethodResource($paymentMethod), 201);
    }
    
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json(new PaymentMethodResource($paymentMethod));
    }
    
    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($request->validated());
        return response()->json(new PaymentMethodResource($paymentMethod));
    }
    
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return response()->json(null, 204);
    }
}
