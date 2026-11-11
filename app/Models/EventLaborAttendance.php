<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Presença confirmada de um convocado no evento (rosto ou assinatura com o dedo). */
class EventLaborAttendance extends Model
{
    protected $fillable = ['event_id', 'collaborator_id', 'method', 'match_score', 'signature_path', 'confirmed_at', 'created_by'];

    protected $casts = [
        'match_score' => 'float',
        'confirmed_at' => 'datetime',
    ];

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path ? asset('storage' . $this->signature_path) : null;
    }

    public function label(): string
    {
        $when = $this->confirmed_at?->format('d/m H:i');
        return ($this->method === 'face' ? 'Reconhecimento facial' : 'Assinatura no celular') . ($when ? ' em ' . $when : '');
    }
}
