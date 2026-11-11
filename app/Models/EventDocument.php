<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Documento guardado no histórico do evento (aba Documentos). */
class EventDocument extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'orcamento' => 'Orçamento',
        'contrato' => 'Contrato',
        'aditivo' => 'Aditivo',
    ];

    public const STATUSES = ['gerado', 'enviado', 'aprovado', 'recusado', 'assinado', 'cancelado'];

    protected $fillable = [
        'event_id',
        'user_id',
        'type',
        'number',
        'code',
        'status',
        'total_value',
        'guests',
        'snapshot',
        'changes',
        'pdf_path',
        'source_type',
        'source_id',
        'observation',
        'decided_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'changes' => 'array',
        'total_value' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function title(): string
    {
        return (self::TYPES[$this->type] ?? ucfirst($this->type)) . ' ' . $this->code;
    }
}
