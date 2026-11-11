<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiddingComment extends Model
{
    protected $fillable = [
        'bidding_id',
        'user_id',
        'comment',
    ];

    public function bidding()
    {
        return $this->belongsTo(Bidding::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
