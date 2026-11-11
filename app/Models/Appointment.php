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
        'summary',
        'summary_status',
        'summary_error',
        'date',
        'start_time',
        'end_time',
        'location',
        'meeting_url',
        'event_id',
        'client_id',
        'partner_id',
        'supplier_id',
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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function participants()
    {
        return $this->hasMany(AppointmentParticipant::class);
    }

    /**
     * Grava a lista de participantes [{type, id}] e mantém client_id/partner_id/supplier_id
     * apontando para o primeiro de cada tipo (compatibilidade).
     */
    public function syncParticipants(array $participants): void
    {
        $rows = collect($participants)
            ->filter(fn ($p) => isset(AppointmentParticipant::TYPES[$p['type'] ?? '']) && !empty($p['id']))
            ->map(fn ($p) => ['participant_type' => $p['type'], 'participant_id' => (int) $p['id']])
            ->unique(fn ($p) => $p['participant_type'] . '-' . $p['participant_id'])
            ->values();

        $this->participants()->delete();
        foreach ($rows as $row) {
            $this->participants()->create($row);
        }

        $first = fn ($type) => optional($rows->firstWhere('participant_type', $type))['participant_id'];
        $this->forceFill([
            'client_id' => $first('client'),
            'partner_id' => $first('partner'),
            'supplier_id' => $first('supplier'),
        ])->saveQuietly();
    }

    public function recordings()
    {
        return $this->hasMany(AppointmentRecording::class);
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
