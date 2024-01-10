<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Contract extends UuidModel implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable;

    protected $fillable = [
        'event_id',
        'version',
        'properties',
        'properties_diff',
        'signed_at',
        'client_id',
        'ip',
    ];

    protected $casts = [
        'properties' => 'array',
        'properties_diff' => 'array',
        'data' => 'array',
    ];

    protected static $sortableFields = [
        'event_id',
        'version',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

