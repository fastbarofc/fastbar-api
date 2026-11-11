<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPlanningPerformance extends Model
{
    protected $fillable = ['event_id', 'planning_released_at', 'released_at', 'staff', 'notes', 'updated_by'];

    protected $casts = ['planning_released_at' => 'date', 'released_at' => 'date', 'staff' => 'boolean'];
}
