<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/** Valores do fechamento de um negócio ganho, informados pela gerência para calcular a comissão. */
class LeadCommission extends Model implements AuditableInterface
{
    use Auditable;

    protected $fillable = ['lead_id', 'ideal_value', 'sold_value', 'gifts_value', 'courtesy_value', 'percent', 'notes', 'updated_by'];

    protected $casts = [
        'ideal_value' => 'decimal:2',
        'sold_value' => 'decimal:2',
        'gifts_value' => 'decimal:2',
        'courtesy_value' => 'decimal:2',
        'percent' => 'decimal:3',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
