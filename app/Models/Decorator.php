<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class Decorator extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes,Sluggable, Auditable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'responsible',
        'phone_1',
        'phone_2'
    ];

    protected static $sortableFields = [
        'name',
        'email',
        'responsible',
    ];

    protected static $filterableFields = [
        'name',
        'email',
        'responsible',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });

        static::updating(function ($model)
        {
            $model->phone_1 = preg_replace("/[^0-9]/", "", $model->phone_1);
            $model->phone_2 = preg_replace("/[^0-9]/", "", $model->phone_2);
        });
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['name']
            ]
        ];
    }
}
