<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Sortable;
use App\Traits\Filterable;


class EventNote extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    protected $fillable = [
        'text',
        'user_id',
        'checklist',
        'event_id',
    ];

    protected static $sortableFields = [
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected static $filterableFields = [
        'user_id'
    ];

    protected $attributes = [
        'checklist' => true,
    ];

    protected $casts = [
        'checklist' => 'boolean'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
