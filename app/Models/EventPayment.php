<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Sortable;
use App\Traits\Filterable;


class EventPayment extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    protected $fillable = [
        'event_id',
        'parthenon_bank_account_id',
        'payment_method_id',
        'value',
        'date',
        'comment',
    ];

    protected static $sortableFields = [
        'event_id',
        'parthenon_bank_account_id',
        'payment_method_id',
        'value',
        'date',

    ];

    protected static $filterableFields = [
        'event_id'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function parthenonBankAccount()
    {
        return $this->belongsTo(ParthenonBankAccount::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
