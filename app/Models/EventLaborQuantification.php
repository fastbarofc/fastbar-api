<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Quantidade de pessoas de um setor em um evento (planilha de quantificação). */
class EventLaborQuantification extends Model
{
    protected $fillable = ['event_id', 'labor_sector_id', 'quantity', 'coordinator_quantity', 'transport_quantity', 'truck_size', 'truck_plate', 'extra_lines', 'excluded', 'notes', 'updated_by'];

    protected $casts = ['quantity' => 'float', 'coordinator_quantity' => 'integer', 'transport_quantity' => 'float', 'excluded' => 'boolean'];

    public function sector()
    {
        return $this->belongsTo(LaborSector::class, 'labor_sector_id');
    }
}
