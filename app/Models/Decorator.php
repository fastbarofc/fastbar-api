<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Decorator extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes,Sluggable, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'responsible',
        'phone_1',
        'phone_2'
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
