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

        // Backfill apenas para instalacoes que ja tinham a Parthenon. Numa
        // instalacao nova da FastBar a empresa nao existe e nao ha o que migrar.
        if (!$parthenon) {
            return;
        }

        $parthenonId = $parthenon->id;

        $collaboratorIds = DB::table('collaborators')->whereNull('deleted_at')->pluck('id');
        $now = now();

        $inserts = $collaboratorIds->map(function ($collaboratorId) use ($parthenonId, $now) {
            return [
                'collaborator_id' => $collaboratorId,
                'company_id' => $parthenonId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('collaborator_companies')->insertOrIgnore($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('collaborator_companies')->truncate();
    }
};
