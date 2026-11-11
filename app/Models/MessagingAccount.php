<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessagingAccount extends Model
{
    use SoftDeletes;

    public const CHANNELS = ['whatsapp', 'instagram'];

    protected $fillable = ['channel', 'name', 'external_id', 'display', 'user_id', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
