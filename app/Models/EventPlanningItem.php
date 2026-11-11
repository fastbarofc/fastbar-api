<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * Custo previsto do planejamento (terceirizado, mão de obra, hotel, lanche).
 * Nasce como "previsto" (não gera pagamento). Ao aprovar, vira uma conta em Contas a pagar.
 */
class EventPlanningItem extends Model implements AuditableInterface
{
    use SoftDeletes, Auditable;

    public const CATEGORIES = [
        'terceirizado' => 'Terceirizado',
        'mao_de_obra' => 'Mão de obra',
        'hotel' => 'Hotel',
        'lanche' => 'Lanche',
    ];

    protected $fillable = [
        'event_id', 'category', 'source', 'office_event_id', 'description', 'supplier_id', 'collaborator_id', 'person_name', 'cpf', 'role',
        'quantity', 'unit_value', 'total_value', 'due_date', 'status', 'approved_by', 'approved_at',
        'payable_id', 'notes', 'user_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_value' => 'float',
        'total_value' => 'float',
        'due_date' => 'date:Y-m-d',
        'approved_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payable()
    {
        return $this->belongsTo(Payable::class);
    }
}
