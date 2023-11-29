<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class File extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'fileable_id',
        'fileable_type',
        'path',
        'name',
        'extension',
    ];
}
