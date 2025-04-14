<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollaboratorGoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'collaborator_id',
        'type',
        'start_date',
        'end_date',
        'expected_contracts',
    ];

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }
}
