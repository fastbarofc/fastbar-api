<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * Substituição nas férias: durante o período, o substituto vê e atende a carteira de leads do consultor ausente.
 * Cadastrada pelo administrador em Configurações > Regras comerciais.
 */
class LeadCover extends Model implements AuditableInterface
{
    use SoftDeletes, Auditable;

    protected $fillable = ['absent_user_id', 'substitute_user_id', 'starts_at', 'ends_at', 'notes', 'created_by'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function absentUser()
    {
        return $this->belongsTo(User::class, 'absent_user_id');
    }

    public function substituteUser()
    {
        return $this->belongsTo(User::class, 'substitute_user_id');
    }

    public function scopeActiveOn($query, ?string $date = null)
    {
        $date ??= Carbon::today()->toDateString();

        return $query->whereDate('starts_at', '<=', $date)->whereDate('ends_at', '>=', $date);
    }

    /** Consultores que o usuário está substituindo hoje. */
    public static function coveredUserIds(int $substituteId): array
    {
        return static::activeOn()->where('substitute_user_id', $substituteId)->pluck('absent_user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Consultores ausentes hoje. */
    public static function absentUserIds(): array
    {
        return static::activeOn()->pluck('absent_user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Substitutos de um consultor hoje. */
    public static function substitutesOf(int $absentId): array
    {
        return static::activeOn()->where('absent_user_id', $absentId)->pluck('substitute_user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
