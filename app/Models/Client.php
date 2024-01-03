<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Client extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable;
        
    const TYPE_FISICAL = 'pf';
    const TYPE_LEGAL = 'pj';

    	protected $fillable = [
        'how_met',
        'type',
        'email',
        'phone_1',
        'phone_2',
        'name',
        'nickname',
        'birth_date',
        'cpf',
        'rg',
        'company_name',
        'fantasy_name',
        'cnpj',
        'ie',
        'im',
        'user_id'
    ];

    protected static $sortableFields = [
        'type',
        'email',
        'name',
        'company_name',
        'fantasy_name',
        'birth_date',
        'nickname',
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

    public function events()
    {
        return $this->belongsToMany(Event::class, 'client_event')->withPivot(['main']);
    }    

    public function user() {
        return $this->belongsTo(User::class);
    }
}
