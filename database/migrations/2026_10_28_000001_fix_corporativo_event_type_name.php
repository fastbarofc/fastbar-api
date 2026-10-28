<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('event_types')
            ->where('name', 'Comporativo')
            ->update(['name' => 'Corporativo', 'slug' => 'corporativo', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('event_types')
            ->where('name', 'Corporativo')
            ->update(['name' => 'Comporativo', 'slug' => 'comporativo', 'updated_at' => now()]);
    }
};
