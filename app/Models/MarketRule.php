<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableInterface;

/**
 * Configurações > Mercado por consultor: uma linha por cidade e segmento (planilha da gerência comercial).
 * Diz quem atende (com mais de um consultor, os leads vão em rodízio), se atendemos e o mínimo de convidados.
 * É daqui que a distribuição automática dos leads lê.
 */
class MarketRule extends Model implements AuditableInterface
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'city', 'state', 'segment', 'user_ids', 'consultant_names', 'min_guests', 'min_guests_high',
        'distance_km', 'potential', 'served', 'notes',
    ];

    protected $casts = [
        'user_ids' => 'array',
        'consultant_names' => 'array',
        'served' => 'boolean',
        'min_guests' => 'integer',
        'min_guests_high' => 'integer',
        'distance_km' => 'integer',
    ];

    /** Regra da cidade (nome digitado no lead, sem acento e sem "- MG") para o segmento. */
    public static function findFor(?string $city, ?string $segment): ?self
    {
        $needle = ServedCity::normalize($city);
        if ($needle === '' || !$segment) {
            return null;
        }

        return static::query()->where('segment', $segment)->get()->first(fn (self $r) => ServedCity::normalize($r->city) === $needle);
    }

    /** A cidade aparece na planilha (em qualquer segmento)? */
    public static function knowsCity(?string $city): bool
    {
        $needle = ServedCity::normalize($city);

        return $needle !== '' && static::query()->get(['city'])->contains(fn (self $r) => ServedCity::normalize($r->city) === $needle);
    }

    /** Consultores: os escolhidos na tela ou, enquanto não houver, os nomes da planilha achados entre os usuários. */
    public function consultantIds(): array
    {
        $ids = array_values(array_filter(array_map('intval', $this->user_ids ?? [])));
        if ($ids) {
            return $ids;
        }
        $found = [];
        foreach ($this->consultant_names ?? [] as $name) {
            if ($user = self::findUserByName($name)) {
                $found[] = (int) $user->id;
            }
        }

        return array_values(array_unique($found));
    }

    /** Nomes para mostrar: usuários escolhidos ou, sem eles, os nomes da planilha (marcados quando não há usuário). */
    public function consultantLabels(): array
    {
        $ids = array_values(array_filter(array_map('intval', $this->user_ids ?? [])));
        if ($ids) {
            return User::whereIn('id', $ids)->pluck('name')->all();
        }

        return array_map(fn ($n) => self::findUserByName($n)?->name ?? "{$n} (sem usuário)", $this->consultant_names ?? []);
    }

    public static function findUserByName(?string $name): ?User
    {
        static $users = null;
        $needle = ServedCity::normalize($name);
        if ($needle === '') {
            return null;
        }
        $users ??= User::query()->where('is_employee', true)->get(['id', 'name']);
        $parts = explode(' ', $needle);

        return $users->first(fn (User $u) => ServedCity::normalize($u->name) === $needle)
            ?? $users->first(function (User $u) use ($parts) {
                $n = ServedCity::normalize($u->name);

                return count($parts) > 1 && str_starts_with($n, $parts[0]) && str_contains($n, end($parts));
            });
    }

    /** Mínimo de convidados para a data (alta temporada usa o mínimo maior). */
    public function minGuestsFor(?string $date, ?CommercialRule $rules = null): ?int
    {
        $rules ??= CommercialRule::current();
        if ($date && $rules->isHighSeason($date) && $this->min_guests_high) {
            return $this->min_guests_high;
        }

        return $this->min_guests;
    }
}
