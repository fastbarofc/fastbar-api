<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class EventStockItem extends Model implements AuditableInterface
{
    use Auditable;

    protected $fillable = [
        'event_stock_id',
        'product_id',
        'expected_quantity',
        'sent_quantity',
        'returned_quantity',
        'extra_quantity',
    ];

    protected $casts = [
        'expected_quantity' => 'float',
        'sent_quantity' => 'float',
        'returned_quantity' => 'float',
        'extra_quantity' => 'float',
    ];

    public function eventStock()
    {
        return $this->belongsTo(EventStock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function movements()
    {
        return $this->morphMany(StockMovement::class, 'movementable');
    }

    public function consumedQuantity(): ?float
    {
        if ($this->returned_quantity === null && $this->extra_quantity === null) {
            return null;
        }

        return round($this->sent_quantity - ($this->returned_quantity ?? 0) + ($this->extra_quantity ?? 0), 2);
    }
}
