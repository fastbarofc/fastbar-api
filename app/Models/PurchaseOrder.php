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

    protected $fillable = [
        'company_id',
        'price',
        'financial_operation_id',
        'cost_center_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected static $sortableFields = [
        'id',
        'price',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'company_id',
        'financial_operation_id',
        'cost_center_id',
    ];

    protected static $searchableFields = [
        'price',
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

    public function products()
    {
        return $this->belongsToMany(Product::class, 'purchase_order_product')
            ->using(PurchaseOrderProduct::class)
            ->withPivot(['purchase_request_id', 'factor', 'reference', 'quantity', 'price'])
            ->withTimestamps();
    }

    public function purchaseOrderProducts()
    {
        return $this->hasMany(PurchaseOrderProduct::class);
    }
} 