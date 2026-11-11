<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'direction', 'type', 'body', 'media_url', 'media_mime', 'media_name',
        'external_id', 'status', 'error', 'user_id', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
