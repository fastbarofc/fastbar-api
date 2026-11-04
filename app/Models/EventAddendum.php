<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAddendum extends Model
{
    public const TYPES = [
        'unit_price' => 'Alteração de valor por pessoa',
        'guests' => 'Adição de convidados',
        'menu' => 'Troca de cardápio',
        'value' => 'Adição de valor',
        'service' => 'Adicional de serviço',
    ];

    protected $table = 'event_addendums';

    protected $fillable = [
        'event_id',
        'number',
        'types',
        'document',
        'previous_total',
        'new_total',
        'user_id',
    ];

    protected $casts = [
        'types' => 'array',
        'document' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCodeAttribute(): string
    {
        return "{$this->event_id}-{$this->number}";
    }
}
