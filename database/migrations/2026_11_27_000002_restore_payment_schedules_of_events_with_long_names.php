<?php

use App\Models\Event;
use App\Services\DataFixSnapshot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    private const SNAPSHOT = '2026_11_27_restore_payment_schedules_of_events_with_long_names';

    // Eventos cujo save quebrava em receivables.description: sem transação, cada tentativa apagava parcelas e
    // recebíveis. Volta o último cronograma completo antes da primeira falha (ids conferidos no banco de produção).
    private const SCHEDULES = [
        558 => ['installments' => [2198, 2199, 2200, 2201], 'receivables' => [2909, 2910, 2911, 2912]],
        560 => ['installments' => [2258, 2259, 2260, 2261], 'receivables' => [2969, 2970, 2971, 2972]],
        561 => ['installments' => [2280, 2281, 2282, 2283], 'receivables' => [2983, 2984, 2985, 2986]],
        564 => ['installments' => [2028, 2029, 2030, 2031], 'receivables' => [2779, 2780, 2781, 2782]],
    ];

    public function up(): void
    {
        $now = now()->toDateTimeString();
        $changes = [];

        foreach (self::SCHEDULES as $eventId => $schedule) {
            $planId = DB::table('payment_plans')->where('event_id', $eventId)->value('id');

            if (!$planId || !$this->isStillBroken($eventId, $planId, $schedule)) {
                continue;
            }

            foreach ($schedule['installments'] as $id) {
                $changes[] = ['table' => 'payment_plan_installments', 'id' => $id, 'set' => ['deleted_at' => null]];
            }

            foreach ($schedule['receivables'] as $id) {
                $changes[] = ['table' => 'receivables', 'id' => $id, 'set' => ['deleted_at' => null]];
            }

            $leftovers = DB::table('payment_plan_installments')
                ->where('payment_plan_id', $planId)
                ->whereNull('deleted_at')
                ->pluck('id');

            foreach ($leftovers as $id) {
                $changes[] = ['table' => 'payment_plan_installments', 'id' => $id, 'set' => ['deleted_at' => $now]];
            }
        }

        app(DataFixSnapshot::class)->apply(self::SNAPSHOT, $changes);
    }

    public function down(): void
    {
        $snapshots = app(DataFixSnapshot::class);

        if (File::exists($snapshots->path(self::SNAPSHOT))) {
            $snapshots->revert(self::SNAPSHOT);
        }
    }

    private function isStillBroken(int $eventId, int $planId, array $schedule): bool
    {
        $eventReceivables = DB::table('receivables')
            ->where('receivableable_type', Event::class)
            ->where('receivableable_id', $eventId);

        if ((clone $eventReceivables)->whereNull('deleted_at')->exists()) {
            return false;
        }

        $trashedReceivables = (clone $eventReceivables)
            ->whereIn('id', $schedule['receivables'])
            ->whereNotNull('deleted_at')
            ->count();

        $trashedInstallments = DB::table('payment_plan_installments')
            ->where('payment_plan_id', $planId)
            ->whereIn('id', $schedule['installments'])
            ->whereNotNull('deleted_at')
            ->count();

        return $trashedReceivables === count($schedule['receivables'])
            && $trashedInstallments === count($schedule['installments']);
    }
};
