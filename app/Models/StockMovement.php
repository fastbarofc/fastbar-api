<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_EVENT_OUT = 'event_out';
    public const TYPE_EVENT_RETURN = 'event_return';
    public const TYPE_EVENT_EXTRA = 'event_extra';

    public const TYPES = [
        self::TYPE_PURCHASE => 'Compra',
        self::TYPE_ADJUSTMENT => 'Ajuste',
        self::TYPE_EVENT_OUT => 'Saída para evento',
        self::TYPE_EVENT_RETURN => 'Retorno de evento',
        self::TYPE_EVENT_EXTRA => 'Consumo extra em evento',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit_price',
        'movementable_type',
        'movementable_id',
        'event_id',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movementable()
    {
        return $this->morphTo();
    }
}
