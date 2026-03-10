<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $parthenon = DB::table('companies')
            ->where('internal_name', 'Parthenon')
            ->whereNull('deleted_at')
            ->first();

        if (!$parthenon) {
            $parthenonId = DB::table('companies')->insertGetId([
                'internal_name' => 'Parthenon',
                'legal_name' => 'Parthenon',
                'trade_name' => 'Parthenon',
                'type' => 'head_office',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $parthenonId = $parthenon->id;
        }

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

        $eventIds = DB::table('events')->whereNull('deleted_at')->pluck('id');
        $now = now();

        $inserts = $eventIds->map(function ($eventId) use ($parthenonId, $now) {
            return [
                'event_id' => $eventId,
                'company_id' => $parthenonId,
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
