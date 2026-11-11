<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Linha do extrato: pendente, conciliada com uma conta a receber/pagar baixada, ou ignorada. */
class BankStatementLine extends Model
{
    protected $fillable = [
        'import_id',
        'parthenon_bank_account_id',
        'txn_date',
        'amount',
        'description',
        'doc_key',
        'status',
        'matched_ref',
    ];

    protected $casts = [
        'txn_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function import()
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }
}
