<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Cviebrock\EloquentSluggable\Sluggable;

class Ceremonial extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Sluggable, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'accountable',
        'email',
        'phone_1',
        'phone_2',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }
}
