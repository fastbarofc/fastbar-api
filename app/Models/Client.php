<?php

namespace App\Models;

use App\Enums\AddressTypeEnum;
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
        'representative_name',
        'representative_cpf',
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
        'type',
        'birth_date',
        'nickname',
    ];

    protected static $searchableFields = [
        'name',
        'email',
        'type',
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

        // Documentos são sempre gravados sem máscara.
        static::saving(function ($model)
        {
            $model->cpf = unmaskCpf($model->cpf);
            $model->cnpj = unmaskCnpj($model->cnpj);
            $model->representative_cpf = unmaskCpf($model->representative_cpf);
        });
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('type', AddressTypeEnum::MAIN->value);
    }

    public function representativeAddress()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('type', AddressTypeEnum::REPRESENTATIVE->value);
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

    public function missingRequiredFields(): array
    {
        $missing = [];

        if (blank($this->name)) {
            $missing[] = 'nome';
        }

        if (blank($this->phone_1)) {
            $missing[] = 'telefone';
        }

        if (!$this->address?->isComplete()) {
            $missing[] = 'endereço';
        }

        if ($this->type === self::TYPE_LEGAL && (
            blank($this->representative_name)
            || blank($this->representative_cpf)
            || !$this->representativeAddress?->isComplete()
        )) {
            $missing[] = 'dados do representante legal';
        }

        return $missing;
    }
}
