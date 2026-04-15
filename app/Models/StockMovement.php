<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class StockMovement extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';

    protected $fillable = [
        'product_id',
        'stock_id',
        'type',
        'quantity',
        'price',
        'movementable_type',
        'movementable_id',
    ];

    protected $casts = [
        'type' => 'string',
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    protected static $sortableFields = [
        'id',
        'quantity',
        'price',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'product_id',
        'stock_id',
        'movementable_type',
        'movementable_id',
    ];

    protected static $searchableFields = [
        'quantity',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function movementable()
    {
        return $this->morphTo();
    }
}

