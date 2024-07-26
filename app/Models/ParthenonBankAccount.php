<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParthenonBankAccount extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    protected $fillable = [
        'bank_name',
        'account_number',
    ];

    protected static $sortableFields = [
        'bank_name',
        'account_number',
    ];

    protected static $filterableFields = [
        'bank_name',
        'account_number',
    ];

    protected static $searchableFields = [
        'bank_name',
    ];
}

