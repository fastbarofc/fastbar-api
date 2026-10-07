<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE event_notes ALTER checklist SET DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE event_notes ALTER checklist SET DEFAULT 0');
    }
};
