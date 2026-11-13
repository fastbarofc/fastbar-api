<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class StockAdjustment extends Model implements AuditableInterface
{
    use Auditable;

    public const REASONS = [
        'count' => 'Contagem de inventário',
        'loss' => 'Perda ou quebra',
        'internal_use' => 'Uso interno',
        'other' => 'Outro',
    ];

    protected $fillable = [
        'product_id',
        'quantity',
        'reason',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->morphMany(StockMovement::class, 'movementable');
    }
}
