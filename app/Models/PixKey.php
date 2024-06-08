<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PixKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['bank_detail_id', 'type', 'key'];

    public function BankDetails()
    {
        return $this->belongsTo(BankDetail::class);
    }
}
