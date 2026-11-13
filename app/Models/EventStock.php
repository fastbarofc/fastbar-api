<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class EventStock extends Model implements AuditableInterface
{
    use Auditable;

    protected $fillable = [
        'event_id',
        'guests',
        'sent_at',
        'sent_by',
        'return_saved_at',
        'return_saved_by',
        'returned_at',
        'returned_by',
        'notes',
    ];

    protected $casts = [
        'guests' => 'integer',
        'sent_at' => 'datetime',
        'return_saved_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function items()
    {
        return $this->hasMany(EventStockItem::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function returnSaver()
    {
        return $this->belongsTo(User::class, 'return_saved_by');
    }

    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
