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
        'name',
        'account_kind',
        'bank_name',
        'agency',
        'account_number',
        'opening_balance',
        'opening_balance_date',
        'card_brand',
        'card_last_digits',
        'closing_day',
        'due_day',
        'active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date:Y-m-d',
        'closing_day' => 'integer',
        'due_day' => 'integer',
        'active' => 'boolean',
    ];

    public const KINDS = ['banco' => 'Banco', 'caixa' => 'Caixa', 'cartao_credito' => 'Cartão de crédito'];

    /** Nome para as telas: o nome dado; senão banco + conta. */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }
        return trim(($this->bank_name ?? '') . ($this->account_number ? ' - ' . $this->account_number : ''));
    }

    public function isCreditCard(): bool
    {
        return $this->account_kind === 'cartao_credito';
    }

    protected static $sortableFields = [
        'id',
        'name',
        'account_kind',
        'bank_name',
        'account_number',
    ];

    protected static $filterableFields = [
        'account_kind',
        'active',
        'bank_name',
        'account_number',
    ];

    protected static $searchableFields = [
        'name',
        'bank_name',
    ];
}

