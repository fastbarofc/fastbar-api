<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PixKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['pix_keyable_id', 'pix_keyable_type', 'type', 'key'];

    public function pixKeyable()
    {
        return $this->morphTo('pix_keyable');
    }
}
