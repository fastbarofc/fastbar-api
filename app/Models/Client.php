<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Client extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;
        
    const TYPE_FISICAL = 'pf';
    const TYPE_LEGAL = 'pj';

    	protected $fillable = [
        'how_did_you_hear_id',
        'type',
        'email',
        'phone_1',
        'phone_2',
        'name',
        'nickname',
        'birth_date',
        'cpf',
        'rg',
        'cnpj',
        'opening_date',
        'ie',
        'im',
        'user_id'
    ];

    protected static $sortableFields = [
        'type',
        'email',
        'name',
        'birth_date',
        'nickname',
    ];
    

    protected static $filterableFields = [
        'id',
        'type',
        'email',
        'name',
        'birth_date',
        'nickname',
    ];

    protected static $searchableFields = [
        'name',
        'email',
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
        return $this->belongsToMany(Event::class, 'client_event');
    }    

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function how_did_you_hear()
    {
        return $this->belongsTo(HowDidYouHear::class);
    }
}
