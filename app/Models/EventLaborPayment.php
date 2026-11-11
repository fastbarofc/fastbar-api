<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Valor a pagar a um colaborador convocado para o evento (Relatório Financeiro da mão de obra). */
class EventLaborPayment extends Model
{
    protected $fillable = [
        'event_id', 'collaborator_id', 'labor_sector_id', 'base_value', 'extra_hours_value', 'after_party_value',
        'coordination_value', 'coordination_role', 'clt_value', 'other_value', 'notes', 'paid_at', 'updated_by',
    ];

    protected $casts = [
        'base_value' => 'float',
        'extra_hours_value' => 'float',
        'after_party_value' => 'float',
        'coordination_value' => 'float',
        'clt_value' => 'float',
        'other_value' => 'float',
        'paid_at' => 'datetime',
    ];

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function sector()
    {
        return $this->belongsTo(LaborSector::class, 'labor_sector_id');
    }

    public function total(): float
    {
        return round($this->base_value + $this->extra_hours_value + $this->after_party_value + $this->coordination_value + $this->clt_value + $this->other_value, 2);
    }
}
