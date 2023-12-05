<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class CollaboratorIncident extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'collaborator_id',
        'description',
    ];

    public function collaborator() {
        return $this->belongsTo(Collaborator::class);
    }
}
