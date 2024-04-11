<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class ExtraSale extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

	protected $fillable = [
        'description',
        'unit_id',
        'quantity',
        'unit_value',
        'event_id',
    ];

    protected static $sortableFields = [
        'description',
        'quantity',
        'unit_value',
    ];

    protected static $filterableFields = [
        'description',
        'quantity',
        'event_id',
    ];

    public function unit() 
    {
        return $this->belongsTo(Unit::class);
    }

    public function event() 
    {
        return $this->belongsTo(Event::class);
    }
}
