<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class StockLevel extends Model implements AuditableInterface
{
    use HasFactory, Auditable, Sortable, Filterable;

    protected $fillable = [
        'product_id',
        'stock_id',
        'current_quantity',
        'weighted_average_price',
        'total_value',
        'min_quantity',
        'max_quantity',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'weighted_average_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
    ];

    protected static $sortableFields = [
        'id',
        'current_quantity',
        'weighted_average_price',
        'total_value',
        'min_quantity',
        'max_quantity',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'product_id',
        'stock_id',
    ];

    protected static $searchableFields = [
        'current_quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
