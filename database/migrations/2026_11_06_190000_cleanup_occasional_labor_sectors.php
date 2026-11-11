<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Setores criados pelo primeiro "+ Setor" (occasional = 1, sem evento) viravam setor de todos os eventos.
 * Agora setor criado na Quantificação é só do evento: os que não estão em uso são apagados e os usados
 * em um único evento passam a ser daquele evento (André, 2026-11-06: setor "teste" não sumia).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('labor_sectors', 'occasional') || !Schema::hasColumn('labor_sectors', 'event_id')) {
            return;
        }
        $ids = DB::table('labor_sectors')->where('occasional', true)->whereNull('event_id')->pluck('id');
        foreach ($ids as $id) {
            $events = DB::table('event_labor_quantifications')->where('labor_sector_id', $id)
                ->where(fn ($q) => $q->where('quantity', '>', 0)->orWhere('coordinator_quantity', '>', 0)->orWhereNotNull('notes'))
                ->pluck('event_id')
                ->merge(DB::table('event_labor_convocations')->where('labor_sector_id', $id)->pluck('event_id'))
                ->merge(DB::table('event_labor_payments')->where('labor_sector_id', $id)->pluck('event_id'))
                ->unique()->values();
            if ($events->isEmpty()) {
                DB::table('event_labor_quantifications')->where('labor_sector_id', $id)->delete();
                DB::table('labor_sectors')->where('id', $id)->delete();
            } elseif ($events->count() === 1) {
                DB::table('labor_sectors')->where('id', $id)->update(['event_id' => $events->first()]);
            }
        }
    }

    public function down(): void
    {
    }
};
