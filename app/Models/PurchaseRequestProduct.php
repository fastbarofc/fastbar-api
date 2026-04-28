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
        'ordered',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'ordered' => 'boolean',
    ];

    protected $attributes = [
        'ordered' => false,
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