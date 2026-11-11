<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockPurchaseItem extends Model
{
    protected $fillable = [
        'stock_purchase_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    public function purchase()
    {
        return $this->belongsTo(StockPurchase::class, 'stock_purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function movements()
    {
        return $this->morphMany(StockMovement::class, 'movementable');
    }
}
