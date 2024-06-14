<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PixKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['collaborator_id', 'type', 'key'];

    public function collaborator()
    {
        return $this->belongsTo(BankDetail::class);
    }
}
