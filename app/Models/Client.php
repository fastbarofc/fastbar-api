<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Client extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    const TYPE_FISICAL = 'pf';
    const TYPE_LEGAL = 'pj';

    protected $fillable = [
        'how_met',
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
    }

    public function fisicalClient()
    {
        return $this->hasOne(FisicalClient::class);
    }

    public function legalClient()
    {
        return $this->hasOne(LegalClient::class);
    }

    public function getCpfAttribute()
    {
        return $this->fisicalClient->cpf;
    }

    public function getCnpjAttribute()
    {
        return $this->legalClient->cnpj;
    }

    public function getRgAttribute()
    {
        return $this->fisicalClient->rg;
    }

    public function getImAttribute()
    {
        return $this->legalClient->im;
    }

    public function getIeAttribute()
    {
        return $this->legalClient->ie;
    }

    public function getBirthDateAttribute()
    {
        return $this->fisicalClient->birth_date;
    }

    public function getNameAttribute()
    {
        return $this->fisicalClient->name;
    }

    public function getCompanyNameAttribute()
    {
        return $this->legalClient->company_name;
    }

    public function getFantasyNameAttribute()
    {
        return $this->legalClient->fantasy_name;
    }

    public function getNicknameAttribute()
    {
        return $this->fisicalClient->nickname;
    }
}
