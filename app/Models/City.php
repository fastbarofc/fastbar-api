<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;


class City extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'state_id',
        'name',
        'iso',
        'iso_ddd',
        'slug',
        'latitude',
        'longitude'
    ];

    protected $with = ["state"];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }

    public function state() {
        return $this->belongsTo(State::class);
    }
}
