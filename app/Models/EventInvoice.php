<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nota fiscal de serviço (NFS-e) de um evento, emitida pela Focus NFe. */
class EventInvoice extends Model
{
    protected $fillable = [
        'event_id',
        'company_id',
        'client_id',
        'ref',
        'environment',
        'status',
        'amount',
        'description',
        'number',
        'verification_code',
        'pdf_url',
        'xml_url',
        'message',
        'last_response',
        'created_by',
        'issued_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'last_response' => 'array',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $hidden = ['last_response'];

    public const STATUS_LABELS = [
        'processando_autorizacao' => 'Processando',
        'autorizado' => 'Emitida',
        'erro_autorizacao' => 'Recusada',
        'cancelado' => 'Cancelada',
        'erro_cancelamento' => 'Erro ao cancelar',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'ref' => $this->ref,
            'environment' => $this->environment,
            'status' => $this->status,
            'status_label' => self::STATUS_LABELS[$this->status] ?? $this->status,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'number' => $this->number,
            'verification_code' => $this->verification_code,
            'pdf_url' => $this->pdf_url,
            'xml_url' => $this->xml_url,
            'message' => $this->message,
            'company' => $this->company ? ['id' => $this->company->id, 'name' => $this->company->trade_name ?: $this->company->legal_name ?: $this->company->internal_name] : null,
            'client' => $this->client ? ['id' => $this->client->id, 'name' => $this->client->name] : null,
            'created_by' => $this->createdBy?->name,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'issued_at' => optional($this->issued_at)->toIso8601String(),
            'cancelled_at' => optional($this->cancelled_at)->toIso8601String(),
        ];
    }
}
