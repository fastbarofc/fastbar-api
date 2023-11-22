<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Http\Resources\PaymentMethodResource;

class PaymentMethodController extends Controller
{
    
    public function index()
    {
        $paymentMethods = PaymentMethod::all();
        return response()->json(PaymentMethodResource::collection($paymentMethods));
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
