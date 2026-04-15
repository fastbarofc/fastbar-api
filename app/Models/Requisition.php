<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Requisition extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    const STATUS_OPEN = 'open';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'event_id',
        'stock_id',
        'destination_stock_id',
        'requester_id',
        'notes',
        'status',
    ];

    protected static $sortableFields = [
        'id',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'event_id',
        'stock_id',
        'requester_id',
    ];

    protected static $searchableFields = [
        'notes',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function destinationStock()
    {
        return $this->belongsTo(Stock::class, 'destination_stock_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function requisitionItems()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'movementable');
    }

    public function devolutions()
    {
        return $this->hasMany(Devolution::class);
    }
}
