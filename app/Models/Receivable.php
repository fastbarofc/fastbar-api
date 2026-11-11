<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Receivable extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable =[
        'description',
        'amount',
        'due_date',
        'paid_at',
        'received_amount',
        'status',
        'payment_method_id',
        'parthenon_bank_account_id',
        'settled_by',
        'financial_operation_id',
        'notes',
        'receivableable_id',
        'receivableable_type'
    ];

    public function receivableable(): MorphTo
    {
        return $this->morphTo();
    }

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
    ];

    public function parthenonBankAccount()
    {
        return $this->belongsTo(ParthenonBankAccount::class);
    }

    public function financialOperation()
    {
        return $this->belongsTo(FinancialOperation::class);
    }

    public function settledBy()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /** Situação para a tela: pago, vencido (cobrar a partir do dia seguinte ao vencimento), vence hoje ou a vencer. */
    public function getSituationAttribute(): string
    {
        if ($this->status === 'paid' || $this->paid_at) {
            return 'paid';
        }
        $today = now()->startOfDay();
        if ($this->due_date && $this->due_date->lt($today)) {
            return 'overdue';
        }
        if ($this->due_date && $this->due_date->isSameDay($today)) {
            return 'today';
        }
        return 'upcoming';
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
