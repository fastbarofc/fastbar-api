<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Sortable;
use App\Traits\Filterable;

class EventBatch extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    protected $fillable = [
        'event_id',
        'unit_value',
        'discount',
        'unit_discount',
        'guests',
        'min_guests',
    ];

    protected static $sortableFields = [
        'event_id',
        'guests',
        'created_at',
        'updated_at'
    ];

    protected static $filterableFields = [
        'event_id',
        'guests',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
