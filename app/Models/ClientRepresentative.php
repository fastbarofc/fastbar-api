<?php

namespace App\Models;

use App\Enums\AddressTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class ClientRepresentative extends Model implements AuditableInterface
{
    use HasFactory, Auditable;

    protected $fillable = [
        'client_id',
        'name',
        'cpf',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $representative) {
            $representative->cpf = unmaskCpf($representative->cpf);
        });

        static::deleting(function (self $representative) {
            $representative->address()->delete();
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('type', AddressTypeEnum::REPRESENTATIVE->value);
    }

    public function isComplete(): bool
    {
        return filled($this->name) && filled($this->cpf) && (bool) $this->address?->isComplete();
    }
}
