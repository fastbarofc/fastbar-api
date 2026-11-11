<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Atividade do lead: tentativa de contato (WhatsApp, ligação, e-mail), reunião, visita, degustação.
 * É o que permite medir a cadência e contar as tentativas antes de "Sem resposta".
 */
class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'result',
        'happened_at',
        'notes',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
