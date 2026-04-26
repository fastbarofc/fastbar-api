<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE purchase_requests SET status = 'confirmed' WHERE status = 'authorized'");

        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('not_confirmed', 'confirmed', 'partially_fulfilled', 'fulfilled') NOT NULL DEFAULT 'not_confirmed'");
    }

    public function down(): void
    {
        DB::statement("UPDATE purchase_requests SET status = 'confirmed' WHERE status IN ('partially_fulfilled', 'fulfilled')");

        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('not_confirmed', 'confirmed', 'authorized') NOT NULL DEFAULT 'not_confirmed'");
    }
};
