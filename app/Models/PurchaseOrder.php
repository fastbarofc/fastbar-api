<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class PurchaseOrder extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    const STATUS_PENDING = 'pending';
    const STATUS_RECEIVED = 'received';
    const STATUS_INVOICE_RECEIVED = 'invoice_received';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_RECEIVED, self::STATUS_CANCELLED],
        self::STATUS_RECEIVED => [self::STATUS_INVOICE_RECEIVED],
        self::STATUS_INVOICE_RECEIVED => [self::STATUS_PAID],
        self::STATUS_PAID => [],
        self::STATUS_CANCELLED => [],
    ];

    protected $fillable = [
        'company_id',
        'price',
        'financial_operation_id',
        'cost_center_id',
        'stock_id',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'string',
    ];

    protected static $sortableFields = [
        'id',
        'price',
        'status',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'company_id',
        'financial_operation_id',
        'cost_center_id',
        'stock_id',
        'status',
    ];

    protected static $searchableFields = [
        'id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialOperation()
    {
        return $this->belongsTo(FinancialOperation::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'purchase_order_product')
            ->using(PurchaseOrderProduct::class)
            ->withPivot(['purchase_request_id', 'factor', 'observation', 'quantity', 'price'])
            ->withTimestamps();
    }

    public function purchaseOrderProducts()
    {
        return $this->hasMany(PurchaseOrderProduct::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'movementable');
    }
} 