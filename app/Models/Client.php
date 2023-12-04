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

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
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
        return !empty($this->fisicalClient) ? $this->fisicalClient->cpf : null; 
    }

    public function getCnpjAttribute()
    {
        return !empty($this->legalClient) ? $this->legalClient->cnpj : null;
    }

    public function getRgAttribute()
    {
        return !empty($this->fisicalClient) ? $this->fisicalClient->rg : null;
    }

    public function getImAttribute()
    {
        return !empty($this->legalClient) ? $this->legalClient->im : null;
    }

    public function getIeAttribute()
    {
        return !empty($this->legalClient) ? $this->legalClient->ie : null;
    }

    public function getBirthDateAttribute()
    {
        return !empty($this->fisicalClient) ? $this->fisicalClient->birth_date : null;
    }

    public function getNameAttribute()
    {
        return !empty($this->fisicalClient) ? $this->fisicalClient->name : null;
    }

    public function getCompanyNameAttribute()
    {
        return !empty($this->legalClient) ? $this->legalClient->company_name : null;
    }

    public function getFantasyNameAttribute()
    {
        return !empty($this->legalClient) ? $this->legalClient->fantasy_name : null;
    }

    public function getNicknameAttribute()
    {
        return !empty($this->fisicalClient) ? $this->fisicalClient->nickname : null;
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'client_event')->withPivot(['main']);
    }    
}
