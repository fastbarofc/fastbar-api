<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;
use App\Traits\IsPerson;

class Collaborator extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'nickname',
        'birth_date',
        'cpf',
        'rg',
        'email',
        'phone_1',
        'phone_2',
        'sector_id',
        'office_id',
        'has_login',
        'user_id'
    ];
    
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}