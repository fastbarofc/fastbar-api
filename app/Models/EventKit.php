<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class EventKit extends Model implements AuditableInterface
{
    use HasFactory, HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'event_kit_id',
        'event_id',
        'name',
        'value',
        'themed_table',
        'ordination',
        'optional',
    ];

    public function event()
    {
        return $this->belongsTo(Kit::class);
    }

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'event_kit_product')->withPivot(['event_id', 'value', 'ordination', 'optional', 'average consumption ', 'quantity', 'comments ']);
    }
}
