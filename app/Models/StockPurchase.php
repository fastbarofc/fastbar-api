<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class StockPurchase extends Model implements AuditableInterface
{
    use Auditable;

    protected $fillable = [
        'purchased_at',
        'supplier',
        'invoice_number',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'purchased_at' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StockPurchaseItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
