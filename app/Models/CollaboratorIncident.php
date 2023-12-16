<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class CollaboratorIncident extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable;

    protected $fillable = [
        'collaborator_id',
        'description',
    ];

    protected static $sortableFields = [
        'description',
    ];
    
    public function collaborator() {
        return $this->belongsTo(Collaborator::class);
    }
}
