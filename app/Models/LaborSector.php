<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Setor da quantificação de mão de obra (colunas da planilha do André). */
class LaborSector extends Model
{
    protected $fillable = ['event_id', 'name', 'color', 'value_table_role', 'has_transport', 'has_coordination', 'has_truck', 'position', 'active', 'occasional'];

    protected $casts = ['has_transport' => 'boolean', 'has_coordination' => 'boolean', 'has_truck' => 'boolean', 'active' => 'boolean', 'occasional' => 'boolean'];
}
