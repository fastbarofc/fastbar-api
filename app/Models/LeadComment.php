<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadComment extends Model
{
    protected $fillable = ['lead_id', 'user_id', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
