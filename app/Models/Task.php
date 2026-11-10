<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

class Task extends Model implements AuditableInterface
{
    use HasFactory, Auditable, SoftDeletes, Sortable, Filterable;

    public const STATUSES = ['todo', 'doing', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const RECURRENCES = ['none', 'daily', 'weekly', 'monthly'];

    protected $fillable = [
        'title',
        'description',
        'sector_id',
        'event_id',
        'due_date',
        'due_time',
        'priority',
        'status',
        'recurrence',
        'next_task_id',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
    ];

    protected static $searchableFields = [
        'title',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Abertas com prazo vencido ou vencendo hoje. */
    public function scopeOverdueOrToday($query)
    {
        return $query->where('status', '!=', 'done')->whereDate('due_date', '<=', now()->toDateString());
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'done')->whereDate('due_date', '<', now()->toDateString());
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->whereHas('users', fn ($q) => $q->where('users.id', $userId));
    }

    /** Tarefas que outra pessoa criou e colocou o usuário como responsável. */
    public function scopeSharedWith($query, int $userId)
    {
        return $query->assignedTo($userId)
            ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', '!=', $userId));
    }

    /** Tudo o que é do usuário: o que ele criou e o que é responsável. */
    public function scopeInvolving($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)->orWhereHas('users', fn ($u) => $u->where('users.id', $userId));
        });
    }

    /** Próximo prazo de uma tarefa que se repete. */
    public function nextDueDate(): ?\Carbon\Carbon
    {
        $base = $this->due_date ? $this->due_date->copy() : now()->startOfDay();

        return match ($this->recurrence) {
            'daily' => $base->addDay(),
            'weekly' => $base->addWeek(),
            'monthly' => $base->addMonthNoOverflow(),
            default => null,
        };
    }
}
