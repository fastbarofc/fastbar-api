<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tarefa básica de uma etapa do planejamento; aparece em todos os eventos. key = event_planning_steps.step_key. */
class PlanningTask extends Model
{
    protected $fillable = ['planning_stage_id', 'key', 'label', 'who', 'sort_order'];

    public function stage()
    {
        return $this->belongsTo(PlanningStage::class, 'planning_stage_id');
    }
}
