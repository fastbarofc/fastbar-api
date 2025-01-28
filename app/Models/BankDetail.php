<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['bank_detailable_id', 'bank_detailable_type', 'bank_name', 'agency', 'account', 'status'];



    public function bankDetailable()
    {
        return $this->morphTo('bank_detailable',);
    }

    

}
