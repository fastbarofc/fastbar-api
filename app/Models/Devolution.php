<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Devolution extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'requisition_id',
        'stock_id',
        'registered_by_id',
        'notes',
    ];

    protected static $sortableFields = [
        'id',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'requisition_id',
        'stock_id',
        'registered_by_id',
    ];

    protected static $searchableFields = [
        'notes',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    public function devolutionItems()
    {
        return $this->hasMany(DevolutionItem::class);
    }

    public function stockMovements()
    {
        return $this->morphMany(StockMovement::class, 'movementable');
    }
}
