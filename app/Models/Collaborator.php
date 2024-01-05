<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Collaborator extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable;

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
        'office_id',
        'has_login',
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

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
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

    public function collaboratorIncidadent()
    {
        return $this->hasMany(CollaboratorIncident::class);
    }
}