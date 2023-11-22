<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    const TYPE_FISICAL = 'pf';
    const TYPE_LEGAL   = 'pj';

    protected $fillable = [
        'type',
        'email',
        'phone_1',
        'phone_2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });

        static::updating(function ($model)
        {
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });
    }

    public function fisicalPerson()
    {
        return $this->hasOne(FisicalPerson::class);
    }

    public function legalPerson()
    {
        return $this->hasOne(LegalPerson::class);
    }

    public function collaborator()
    {
        return $this->hasOne(Collaborator::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }
}
