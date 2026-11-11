<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Divergência encontrada na conferência contrato x sistema (1.2). */
class EventPlanningDivergence extends Model
{
    protected $fillable = ['event_id', 'description', 'user_id', 'notified_user_id', 'notified_names', 'resolved_at', 'resolved_by'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiedUser()
    {
        return $this->belongsTo(User::class, 'notified_user_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
