<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Contract extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'event_id',
        'version',
        'properties',
        'properties_diff',
    ];

    protected $casts = [
        'properties' => 'array',
        'properties_diff' => 'array'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

