<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use App\Traits\Sortable;


class Stock extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    protected $fillable = [
        'name'
    ];

    protected static $sortableFields = [
        'name'
    ];

    protected static $filterableFields = [
        'name'
    ];
    
    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
