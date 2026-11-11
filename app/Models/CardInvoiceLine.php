<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Linha da fatura do cartão importada: pendente, vinculada a uma conta a pagar, lançada ou ignorada. */
class CardInvoiceLine extends Model
{
    protected $fillable = [
        'card_account_id',
        'due_date',
        'txn_date',
        'description',
        'amount',
        'installment_number',
        'installment_total',
        'doc_key',
        'file_name',
        'status',
        'payable_id',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'txn_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function card()
    {
        return $this->belongsTo(ParthenonBankAccount::class, 'card_account_id');
    }

    public function payable()
    {
        return $this->belongsTo(Payable::class);
    }
}
