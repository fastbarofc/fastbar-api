<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['collaborator_id', 'bank_name', 'agency', 'account', 'status'];



    public function colaborador()
    {
        return $this->belongsTo(Collaborator::class);
    }

    

}
