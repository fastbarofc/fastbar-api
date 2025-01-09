<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Collaborator extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'nickname',
        'birth_date',
        'cpf',
        'rg',
        'email',
        'phone_1',
        'phone_2',
        'sector_id',
        'sector_office_id',
        'active',
        'user_id',
        'file_id',


    ];

    protected $casts = [
        'has_login' => 'boolean',
        'active' => 'boolean'
    ];

    protected static $sortableFields = [
        'name',
        'nickname',
        'birth_date',
        'email',
        'sector.name',    
        'sectorOffice.name',
    ];

    protected static $filterableFields = [
        'id',
        'sector_id',
        'sector_office_id',
        'nickname',
        'name',
        'email',
        'phone_1'
    ];

    protected static $searchableFields = [
        'name',
        'nickname',
        'email',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->cpf = preg_replace("/[^0-9]/", "", $model->cpf);
            $model->rg = preg_replace("/[^0-9]/", "", $model->rg);
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });

        static::updating(function ($model)
        {
            $model->cpf = preg_replace("/[^0-9]/", "", $model->cpf);
            $model->rg = preg_replace("/[^0-9]/", "", $model->rg);
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });
    }

    public function scopeOrderByRelation(Builder $query, $relation, $field, $direction = 'asc')
    {
        $relatedModel = $this->{$relation}()->getRelated();
        
        $relatedTable = $relatedModel->getTable();
    
        $foreignKey = $this->{$relation}()->getForeignKeyName();
    
    
        $query->select("{$this->getTable()}.*")
              ->leftJoin("{$relatedTable}", "{$relatedTable}.id", '=', "{$this->getTable()}.{$foreignKey}")
              ->orderBy("{$relatedTable}.{$field}", $direction);
    
        return $query->groupBy("{$this->getTable()}.id");
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function sectorOffice()
    {
        return $this->belongsTo(SectorOffice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function qualifications() 
    {
        return $this->belongsToMany(Qualification::class, 'collaborator_qualification')->withPivot(['points']);
    }

    public function getPhotoAttribute()
    {
        return $this->file?->path;
    }

    public function collaboratorIncidents()
    {
        return $this->hasMany(CollaboratorIncident::class);
    }


    public function bankDetails()
    {
        return $this->hasMany(BankDetail::class);
    }

    public function pixKey()
    {
        return $this->hasMany(PixKey::class);
    }

    public function officeEvents()
    {
        return $this->belongsToMany(OfficeEvent::class, 'event_office_collaborators');
    }

    public function officeBudgets()
    {
        return $this->belongsToMany(OfficeEvent::class, 'budget_office_collaborators');
    }
}

