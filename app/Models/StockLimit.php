<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class StockLimit extends Model implements AuditableInterface
{
    use Auditable;

    protected $fillable = [
        'product_id',
        'min_quantity',
        'max_quantity',
        'user_id',
    ];

    protected $casts = [
        'min_quantity' => 'float',
        'max_quantity' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
