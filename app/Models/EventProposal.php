<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventProposal extends Model
{
    protected $fillable = [
        'event_id',
        'data',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
