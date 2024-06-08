<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Sortable;
use App\Traits\Filterable;


class EventNote extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    protected $fillable = [
        'text',
        'collaborator_id',
        'checklist',
        'event_id'
    ];

    protected static $sortableFields = [
        'collaborator_id'
    ];

    protected static $filterableFields = [
        'collaborator_id'
    ];

    protected $casts = [
        'checklist' => 'boolean'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }
}
