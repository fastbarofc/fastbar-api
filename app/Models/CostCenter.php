<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class CostCenter extends Model
{
    use HasFactory, Sortable, Filterable, SoftDeletes;
    
    protected $fillable = [
        'code',
        'description',
        'account',
    ];

    protected $casts = [
        'account' => 'string',
    ];

    protected static $sortableFields = [
        'id',
        'code',
        'description',
        'account',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'code',
        'description',
        'account',
    ];

    protected static $searchableFields = [
        'code',
        'description',
        'account',
    ];
}
