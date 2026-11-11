<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'messaging_account_id', 'channel', 'contact_external_id', 'contact_name', 'contact_phone', 'contact_username',
        'user_id', 'lead_id', 'client_id', 'status', 'internal', 'last_message_at', 'last_direction',
        'last_message_preview', 'unread_count', 'waiting_since',
    ];

    protected $casts = [
        'internal' => 'boolean',
        'last_message_at' => 'datetime',
        'waiting_since' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(MessagingAccount::class, 'messaging_account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
