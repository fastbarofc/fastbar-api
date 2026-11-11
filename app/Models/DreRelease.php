<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Liberação do DRE de um mês (sem registro = DRE provisório). */
class DreRelease extends Model
{
    protected $fillable = ['month', 'released_by', 'released_by_name', 'released_at', 'had_pending'];

    protected $casts = [
        'month' => 'date:Y-m-d',
        'released_at' => 'datetime',
        'had_pending' => 'boolean',
    ];
}
