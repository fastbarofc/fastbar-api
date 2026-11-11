<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Link sem login da tela de presença de um evento (para o coordenador). */
class EventAttendanceLink extends Model
{
    protected $fillable = ['event_id', 'token', 'valid_from', 'valid_until', 'created_by'];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function isActive(): bool
    {
        $today = now()->startOfDay();
        return $today->gte($this->valid_from->copy()->startOfDay()) && $today->lte($this->valid_until->copy()->startOfDay());
    }

    public function toInfo(): array
    {
        return [
            'token' => $this->token,
            'valid_from' => $this->valid_from?->format('Y-m-d'),
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'active' => $this->isActive(),
        ];
    }
}
