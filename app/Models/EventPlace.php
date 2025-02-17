<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;




class EventPlace extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sluggable, Sortable, Filterable;

    protected $fillable = [
        'name',
        'slug',
        'responsible',
        'description',
        'phone_1',
        'phone_2',
        'email',
        'belongs_parthenon',
    ];

    protected static $sortableFields = [
        'name',
        'responsible',
        'description',
        'email',
    ];

    protected static $filterableFields = [
        'name',
        'responsible',
        'description',
        'email',
        'belongs_parthenon',
    ];

    protected static $searchableFields = [
        'name',
        'responsible',
        'email'
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

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
