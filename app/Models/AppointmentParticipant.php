<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AppointmentParticipant extends Model
{
    public $timestamps = false;

    public const TYPES = [
        'client' => Client::class,
        'partner' => Partner::class,
        'supplier' => Supplier::class,
        'collaborator' => Collaborator::class,
    ];

    public const LABELS = [
        'client' => 'Cliente',
        'partner' => 'Parceiro',
        'supplier' => 'Fornecedor',
        'collaborator' => 'Colaborador',
    ];

    protected $fillable = ['appointment_id', 'participant_type', 'participant_id'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Devolve [{type, id, name, label}] buscando os nomes de uma vez por tipo.
     */
    public static function describe(Collection $participants): array
    {
        $names = [];
        $phones = [];
        foreach ($participants->groupBy('participant_type') as $type => $items) {
            $class = self::TYPES[$type] ?? null;
            if (!$class) {
                continue;
            }
            $ids = $items->pluck('participant_id')->unique()->all();
            $records = $class::withoutGlobalScopes()->whereIn('id', $ids)->get();
            foreach ($records as $record) {
                $names[$type][$record->id] = self::nameOf($type, $record);
                $phones[$type][$record->id] = $record->phone_1 ?: ($record->phone_2 ?? null);
            }
        }

        return $participants->map(fn ($p) => [
            'type' => $p->participant_type,
            'id' => (int) $p->participant_id,
            'name' => $names[$p->participant_type][$p->participant_id] ?? '(excluído)',
            'label' => self::LABELS[$p->participant_type] ?? $p->participant_type,
            'phone' => $phones[$p->participant_type][$p->participant_id] ?? null,
        ])->values()->all();
    }

    public static function nameOf(string $type, $record): string
    {
        if ($type === 'supplier') {
            return $record->fantasy_name ?: $record->corporate_name;
        }
        return (string) $record->name;
    }
}
