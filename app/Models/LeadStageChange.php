<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Uma mudança de etapa do lead no funil (histórico usado nos gráficos do Comercial). */
class LeadStageChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['lead_id', 'from_stage', 'to_stage', 'user_id', 'changed_at'];

    protected $casts = ['changed_at' => 'datetime'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
