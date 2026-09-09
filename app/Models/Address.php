<?php

namespace App\Models;

use App\Enums\AddressTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use \OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;


class Address extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'type',
        'street',
        'district',
        'complement',
        'city_id',
        'state_id',
        'number',
        'zip_code',
        'reference'
    ];

    protected $casts = [
        'type' => AddressTypeEnum::class,
    ];

    public function addressable() {
        return $this->morphTo();
    }

    public function city() {
        return $this->belongsTo(City::class);
    }

    public function state() {
        return $this->belongsTo(State::class);
    }
}
