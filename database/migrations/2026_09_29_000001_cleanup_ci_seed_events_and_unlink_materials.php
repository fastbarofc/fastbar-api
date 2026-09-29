<?php

use App\Models\Devolution;
use App\Models\Event;
use App\Models\Requisition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Seeder rodado em produção pela CI: eventos 1 a 200, todos criados neste intervalo.
    private const SEED_START = '2026-09-15 17:43:00';
    private const SEED_END = '2026-09-15 17:44:59';
    private const SEED_EVENT_COUNT = 200;

    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        DB::transaction(function () {
            $seedIds = $this->seedEventIds();

            $keepIds = DB::table('events')
                ->whereIn('id', $seedIds)
                ->groupBy('event_status_id')
                ->selectRaw('MIN(id) as id')
                ->pluck('id');

            $this->deleteEvents($seedIds->diff($keepIds)->values());

            DB::table('event_material')->delete();
        });
    }

    public function down(): void
    {
    }

    private function seedEventIds(): Collection
    {
        $ids = DB::table('events')
            ->whereBetween('created_at', [self::SEED_START, self::SEED_END])
            ->pluck('id');

        if ($ids->count() !== self::SEED_EVENT_COUNT || (int) $ids->min() !== 1 || (int) $ids->max() !== self::SEED_EVENT_COUNT) {
            throw new RuntimeException('Eventos do seeder da CI não conferem com o esperado (ids 1 a 200). Limpeza abortada.');
        }

        return $ids;
    }

    private function deleteEvents(Collection $ids): void
    {
        $kitIds = DB::table('event_kits')->whereIn('event_id', $ids)->pluck('id');
        DB::table('event_kit_product')->whereIn('event_kit_id', $kitIds)->delete();
        DB::table('event_kits')->whereIn('id', $kitIds)->update(['event_kit_id' => null]);
        DB::table('event_kits')->whereIn('id', $kitIds)->delete();

        $requisitionIds = DB::table('requisitions')->whereIn('event_id', $ids)->pluck('id');
        $devolutionIds = DB::table('devolutions')->whereIn('requisition_id', $requisitionIds)->pluck('id');
        DB::table('stock_movements')
            ->where('movementable_type', Requisition::class)
            ->whereIn('movementable_id', $requisitionIds)
            ->delete();
        DB::table('stock_movements')
            ->where('movementable_type', Devolution::class)
            ->whereIn('movementable_id', $devolutionIds)
            ->delete();

        DB::table('receivables')
            ->where('receivableable_type', Event::class)
            ->whereIn('receivableable_id', $ids)
            ->delete();

        $tablesWithoutCascade = [
            'client_event',
            'contracts',
            'event_gifts',
            'event_material',
            'event_notes',
            'event_office_budget',
            'event_office_event',
            'event_utensil',
            'extra_sales',
        ];

        foreach ($tablesWithoutCascade as $table) {
            DB::table($table)->whereIn('event_id', $ids)->delete();
        }

        DB::table('events')->whereIn('event_id', $ids)->update(['event_id' => null]);
        DB::table('events')->whereIn('id', $ids)->delete();
    }
};
