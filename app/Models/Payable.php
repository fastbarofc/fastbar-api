<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payable extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'description',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'financial_operation_id'
    ];

    public function payableable()
    {
        return $this->morphTo();
    }

    public function financialOperation()
    {
        return $this->belongsTo(FinancialOperation::class);
    }
}
