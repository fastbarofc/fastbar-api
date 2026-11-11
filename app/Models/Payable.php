<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Payable extends Model implements AuditableInterface
{
    use HasFactory, softDeletes, Auditable;

    protected $fillable = [
        'event_id',
        'description',
        'category',
        'amount',
        'due_date',
        'paid_at',
        'paid_amount',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'financial_operation_id',
        'cost_center_id',
        'supplier_id',
        'partner_id',
        'collaborator_id',
        'payment_method_id',
        'parthenon_bank_account_id',
        'card_account_id',
        'notes',
        'payableable_id',
        'payableable_type'
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function parthenonBankAccount()
    {
        return $this->belongsTo(ParthenonBankAccount::class);
    }

    public function cardAccount()
    {
        return $this->belongsTo(ParthenonBankAccount::class, 'card_account_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Situação para a tela: aguardando aprovação, recusada, paga, vencida, vence hoje ou a vencer. */
    public function getSituationAttribute(): string
    {
        if ($this->approval_status === 'pending') {
            return 'awaiting';
        }
        if ($this->approval_status === 'rejected') {
            return 'rejected';
        }
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

    public function payableable()
    {
        return $this->morphTo();
    }

    public function financialOperation()
    {
        return $this->belongsTo(FinancialOperation::class);
    }
}
