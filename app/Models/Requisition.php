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

    protected $fillable = [
        'event_id',
        'department_id',
        'destination_department_id',
        'requester_id',
        'notes',
    ];

    protected static $sortableFields = [
        'id',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'event_id',
        'department_id',
        'requester_id',
    ];

    protected static $searchableFields = [
        'notes',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function destinationDepartment()
    {
        return $this->belongsTo(Department::class, 'destination_department_id');
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

    public function devolution()
    {
        return $this->hasOne(Devolution::class);
    }
}
