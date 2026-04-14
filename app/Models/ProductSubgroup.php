<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use OwenIt\Auditing\Auditable;

class ProductSubgroup extends Model implements AuditableInterface
{
    use HasFactory, SoftDeletes, Auditable, Sortable, Filterable;

    protected $fillable = ['slug', 'description', 'product_group_id'];

    protected static $sortableFields = ['description'];

    protected static $filterableFields = ['description', 'product_group_id'];

    protected static $searchableFields = ['description'];

    public function productGroup()
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
