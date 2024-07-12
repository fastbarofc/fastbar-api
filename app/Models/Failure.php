<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Failure extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = ['description', 'percentage'];

    public static function sortableFields()
    {
        return ['description', 'percentage'];
    }

    public static function filterableFields()
    {
        return ['description', 'percentage'];
    }

    public function events()
    {
        return $this->belongsToMany(Event::class)->withPivot('deduct');
    }
}
