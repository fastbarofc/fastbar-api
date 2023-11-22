<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;




class EventPlace extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'responsible',
        'description',
        'phone_1',
        'phone_2',
        'email'
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
