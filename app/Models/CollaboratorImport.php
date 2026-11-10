<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollaboratorImport extends Model
{
    protected $fillable = ['type', 'file_name', 'file_path', 'user_id', 'summary'];

    protected $casts = [
        'summary' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
