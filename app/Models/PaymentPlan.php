<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'total_amount',
        'down_payment',
        'down_payment_due_date',
        'num_installments',
        'installment_amount',
        'payment_method_id',
        'due_day'
    ];

    protected $casts = [
        'down_payment_due_date' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }
}
