<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class EventStatus extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable, Sortable, Filterable;

    // Até a Pré-Reserva, cliente, parceiro e local podem ter só o nome; daqui em diante o cadastro precisa estar completo.
    public const REQUIRES_COMPLETE_REGISTRATIONS = ['Contrato', 'Fechado', 'Planejamento', 'Concluído'];

    public const REQUIRES_REASON = ['Cancelado', 'Descartado'];

    public const LOST = ['Cancelado', 'Descartado', 'Fechou com o concorrente'];

    public const NEGOTIATING = ['Aberto', 'Pré-Reserva'];

    public const CONFIRMED = ['Contrato', 'Fechado', 'Planejamento', 'Concluído'];

    // Pré-Reserva não pode cair no local e dia de um evento confirmado, mas duas pré-reservas convivem.
    public const CHECKS_PLACE_CONFLICT = ['Pré-Reserva', 'Contrato', 'Fechado', 'Planejamento', 'Concluído'];

    protected $fillable = [
        'name',
        'slug'
    ];

    protected static $sortableFields = [
        'name',
    ];

    protected static $filterableFields = [
        'name',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function eventStatusHistory()
    {
        return $this->hasMany(EventStatusHistory::class);
    }

}
