<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PurchaseRequestProduct extends Pivot
{
    use HasFactory;

    protected $table = 'purchase_request_product';

    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'factor',
        'observation',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 