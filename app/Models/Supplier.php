<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Supplier extends Model implements AuditableInterface
{
    use HasFactory, softDeletes, Sortable, Auditable, Filterable;

    protected $fillable = [
        'corporate_name',
        'fantasy_name',
        'cnpj',
        'state_registration',
        'phone_1',
        'phone_2',
        'bank',
        'agency',
        'account',
        'pix_key',
        'account_holder',
        'observations',
        'user_id',
    ];

    protected static $sortableFields = [
        'corporate_name',
        'fantasy_name',
        'cnpj',
        'state_registration',
    ];

    protected static $filterableFields = [
        'id',
        'corporate_name',
        'fantasy_name',
        'cnpj',
        'state_registration',
    ];

    protected static $searchableFields = [
        'corporate_name',
        'fantasy_name',
        'cnpj',
        'state_registration',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
