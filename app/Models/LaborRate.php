<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Valor da diária da mão de obra por função, cidade ou faixa de distância (sem cidade e sem faixa = valor padrão). */
class LaborRate extends Model
{
    use SoftDeletes;

    protected $fillable = ['office_event_id', 'role', 'city', 'km_from', 'km_to', 'daily_value', 'notes'];

    protected $casts = [
        'km_from' => 'float',
        'km_to' => 'float',
        'daily_value' => 'float',
    ];

    public function officeEvent()
    {
        return $this->belongsTo(OfficeEvent::class);
    }

    /** Melhor valor para a função: cidade igual > faixa de distância > padrão. */
    public static function findFor(?int $officeEventId, ?string $city, ?float $km): ?self
    {
        $rates = self::where('office_event_id', $officeEventId)->get();
        $norm = fn ($s) => mb_strtolower(trim((string) $s));
        if ($city) {
            $byCity = $rates->first(fn ($r) => $r->city && $norm($r->city) === $norm($city));
            if ($byCity) {
                return $byCity;
            }
        }
        if ($km !== null) {
            $byKm = $rates->first(fn ($r) => !$r->city && ($r->km_from !== null || $r->km_to !== null)
                && ($r->km_from === null || $km >= $r->km_from) && ($r->km_to === null || $km <= $r->km_to));
            if ($byKm) {
                return $byKm;
            }
        }
        return $rates->first(fn ($r) => !$r->city && $r->km_from === null && $r->km_to === null);
    }
}
