<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use App\Traits\IsPerson;

class Client extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, IsPerson;

    protected $fillable = [
        'fisical_person_id',
        'how_met',
    ];

    public function fisicalPerson()
    {
        return $this->belongsTo(FisicalPerson::class);
    }
}
