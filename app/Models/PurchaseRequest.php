<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class PurchaseRequest extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    const STATUS_NOT_CONFIRMED = 'not_confirmed';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PARTIALLY_FULFILLED = 'partially_fulfilled';
    const STATUS_FULFILLED = 'fulfilled';

    const FULFILLMENT_STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_PARTIALLY_FULFILLED,
        self::STATUS_FULFILLED,
    ];

    protected $fillable = [
        'company_id',
        'requester_id',
        'sector_id',
        'expected_date',
        'status',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'status' => 'string',
    ];

    protected static $sortableFields = [
        'id',
        'expected_date',
        'status',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'company_id',
        'requester_id',
        'sector_id',
        'status',
        'expected_date',
    ];

    protected static $searchableFields = [
        'id',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function purchaseOrderProducts()
    {
        return $this->hasMany(PurchaseOrderProduct::class);
    }

    public function purchaseRequestProducts()
    {
        return $this->hasMany(PurchaseRequestProduct::class);
    }


}
