<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class EventStatusHistory extends Model implements AuditableInterface
{
    use HasFactory,  SoftDeletes, Auditable;

    protected $fillable = [
        'event_id',
        'event_status_id',
        'collaborator_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    
    public function eventStatus()
    {
        return $this->belongsTo(EventStatus::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }
}
