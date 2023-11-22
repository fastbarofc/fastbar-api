<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class FisicalPerson extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'nickname',
        'birth_date',
        'cpf',
        'rg',
        'person_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->cpf = preg_replace("/[^0-9]/", "", $model->cpf);
        });

        static::updating(function ($model)
        {
            $model->cpf = preg_replace("/[^0-9]/", "", $model->cpf);
        });
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }


}
