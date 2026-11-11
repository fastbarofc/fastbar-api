<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Extrato bancário importado (OFX/CSV) para a conciliação bancária. */
class BankStatementImport extends Model
{
    protected $fillable = [
        'parthenon_bank_account_id',
        'file_name',
        'file_format',
        'period_start',
        'period_end',
        'closing_balance',
        'closing_balance_date',
        'lines_total',
        'lines_new',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'closing_balance_date' => 'date:Y-m-d',
        'closing_balance' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(ParthenonBankAccount::class, 'parthenon_bank_account_id');
    }

    public function lines()
    {
        return $this->hasMany(BankStatementLine::class, 'import_id');
    }
}
