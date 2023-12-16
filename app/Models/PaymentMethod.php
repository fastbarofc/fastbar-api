<?php

namespace App\Models;

use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class PaymentMethod extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable;

    protected $fillable = [
        'name',
        'slug'
    ];

    protected static $sortableFields = [
        'name',
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
