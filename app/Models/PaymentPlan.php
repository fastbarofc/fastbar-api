<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'total_amount', 'down_payment',
        'num_installments', 'installment_amount',
        'payment_method_id', 'due_day'
    ];

    public function event() {
        return $this->belongsTo(Event::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }

    public function paymentMethod() {
        return $this->belongsTo(PaymentMethod::class);
    }
}
