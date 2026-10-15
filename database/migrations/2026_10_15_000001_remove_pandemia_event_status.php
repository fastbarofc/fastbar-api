<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Soft delete: o histórico de status ainda aponta para Pandemia.
    public function up(): void
    {
        $statusIds = DB::table('event_statuses')
            ->whereIn('name', ['Pandemia', 'Descartado'])
            ->whereNull('deleted_at')
            ->pluck('id', 'name');

        if (!isset($statusIds['Pandemia'])) {
            return;
        }

        DB::table('events')
            ->where('event_status_id', $statusIds['Pandemia'])
            ->update(['event_status_id' => $statusIds['Descartado']]);

        DB::table('event_statuses')->where('id', $statusIds['Pandemia'])->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        DB::table('event_statuses')->where('name', 'Pandemia')->update(['deleted_at' => null]);
    }
};
