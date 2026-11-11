<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Etapa do planejamento (Configurações > Planejamento > Etapas do planejamento). */
class PlanningStage extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function tasks()
    {
        return $this->hasMany(PlanningTask::class)->orderBy('sort_order')->orderBy('id');
    }
}
