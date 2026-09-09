<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cria a empresa Fastbar e faz o backfill de event_companies.
 *
 * O backfill so acontece se a Parthenon ja existir no banco (instalacao
 * antiga, onde os eventos precisavam ser atribuidos a ela). Numa instalacao
 * nova da FastBar a Parthenon nao e criada, e nao ha eventos para atribuir.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fastbar = DB::table('companies')
            ->where('internal_name', 'Fastbar')
            ->whereNull('deleted_at')
            ->first();

        if (!$fastbar) {
            DB::table('companies')->insert([
                'internal_name' => 'Fastbar',
                'legal_name' => 'Fastbar',
                'trade_name' => 'Fastbar',
                'type' => 'head_office',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $parthenon = DB::table('companies')
            ->where('internal_name', 'Parthenon')
            ->whereNull('deleted_at')
            ->first();

        if (!$parthenon) {
            return;
        }

        $eventIds = DB::table('events')->whereNull('deleted_at')->pluck('id');
        $now = now();

        $inserts = $eventIds->map(function ($eventId) use ($parthenon, $now) {
            return [
                'event_id' => $eventId,
                'company_id' => $parthenon->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('event_companies')->insertOrIgnore($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('event_companies')->truncate();
    }
};
