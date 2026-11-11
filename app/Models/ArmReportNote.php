<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Texto de uma página do documento ARM de um mês. */
class ArmReportNote extends Model
{
    protected $fillable = ['year', 'month', 'section', 'body', 'updated_by'];
}
