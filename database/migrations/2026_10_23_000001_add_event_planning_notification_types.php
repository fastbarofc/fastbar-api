<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TYPES = [
        'event_moved_to_planning' => 'Evento movido automaticamente para Planejamento',
        'event_planning_blocked' => 'Evento não pôde ir automaticamente para Planejamento',
    ];

    public function up(): void
    {
        foreach (self::TYPES as $name => $description) {
            DB::table('notification_types')->insertOrIgnore([
                'name' => $name,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('notification_types')->whereIn('name', array_keys(self::TYPES))->delete();
    }
};
