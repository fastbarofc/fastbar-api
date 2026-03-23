<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolutionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'devolution_id',
        'product_id',
        'quantity_returned',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
    ];

    public function devolution()
    {
        return $this->belongsTo(Devolution::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
