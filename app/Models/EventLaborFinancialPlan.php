<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLaborFinancialPlan extends Model
{
    protected $fillable = ['event_id', 'extra_lines', 'costs_confirmed', 'updated_by'];

    protected $casts = [
        'extra_lines' => 'array',
        'costs_confirmed' => 'boolean',
    ];
}
