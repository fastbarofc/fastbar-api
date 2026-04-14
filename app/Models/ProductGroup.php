<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class ProductGroup extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = ['slug', 'description'];

    protected static $sortableFields = ['description'];

    protected static $filterableFields = ['description'];

    protected static $searchableFields = ['description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subgroups()
    {
        return $this->hasMany(ProductSubgroup::class);
    }
}
