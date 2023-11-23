<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Eventkit extends Model implements AuditableInterface
{
    use HasFactory, HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'value',
        'themed_table',
        'ordination',
        'aptional',
    ];

    public function kits()
    {
        return $this->belongsToMany(kit::class, 'kit_product')->withPivot(['value', 'themed_table', 'optional']);
    }
}
