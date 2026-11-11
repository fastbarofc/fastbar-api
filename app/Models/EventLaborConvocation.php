<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Colaborador convocado para um setor do evento. */
class EventLaborConvocation extends Model
{
    protected $fillable = ['event_id', 'labor_sector_id', 'collaborator_id', 'is_coordinator', 'created_by'];

    protected $casts = ['is_coordinator' => 'boolean'];

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function sector()
    {
        return $this->belongsTo(LaborSector::class, 'labor_sector_id');
    }
}
