<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products CHANGE average_consumption consumption_per_guest DECIMAL(10,5) NULL');
        DB::statement('ALTER TABLE products CHANGE manufacturer volume DECIMAL(10,3) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products CHANGE consumption_per_guest average_consumption DOUBLE(8,2) NULL');
        DB::statement('UPDATE products SET volume = NULL');
        DB::statement('ALTER TABLE products CHANGE volume manufacturer VARCHAR(191) NULL');
    }
};
