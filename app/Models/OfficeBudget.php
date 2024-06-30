<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use App\Traits\Sortable;

class OfficeBudget extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'slug'
    ];

    protected static $sortableFields = [
        'name'
    ];

    protected static $filterableFields = [
        'name'
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['description']
            ]
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function collaborators()
    {
        return $this->belongsToMany(Collaborator::class, 'budget_office_collaborators');
    }
}
