<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalClient extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'company_name',
        'fantasy_name',
        'cnpj',
        'ie',
        'im',
        'client_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->cnpj = preg_replace("/[^0-9]/", "", $model->cnpj);
        });

        static::updating(function ($model)
        {
            $model->cnpj = preg_replace("/[^0-9]/", "", $model->cnpj);
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
