<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class FinancialOperation extends Model
{
    use HasFactory, Sortable, Filterable, SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'account',
        'nature',
        'type',
    ];

    protected $casts = [
        'account' => 'string',
        'nature' => 'string',
        'type' => 'string',
    ];

    protected static $sortableFields = [
        'id',
        'code',
        'description',
        'account',
        'nature',
        'type',
        'created_at',
    ];

    protected static $filterableFields = [
        'id',
        'code',
        'description',
        'account',
        'nature',
        'type',
    ];

    protected static $searchableFields = [
        'code',
        'description',
    ];

    public function payables()
    {
        return $this->hasMany(Payable::class);
    }
}
