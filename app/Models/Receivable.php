<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Receivable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =[
        'description',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method_id',
        'receivableable_id',
        'receivableable_type'
    ];

    public function receivableable(): MorphTo
    {
        return $this->morphTo();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
