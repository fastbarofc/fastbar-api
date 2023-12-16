<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name',
        'letter',
        'iso',
        'slug'
    ];


    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function cities() {
        return $this->hasMany(City::class);
    }
}
