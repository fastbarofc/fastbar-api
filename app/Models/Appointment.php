<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/** Compromisso no calendário de Tarefas (ex.: reunião). */
class Appointment extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'location',
        'event_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Compromissos que o usuário criou ou em que é participante. */
    public function scopeInvolving($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)->orWhereHas('users', fn ($u) => $u->where('users.id', $userId));
        });
    }
}
