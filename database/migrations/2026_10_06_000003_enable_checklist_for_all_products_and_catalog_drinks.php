<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->update(['display_checklist' => true]);
        DB::table('kit_product')->update(['checklist' => true]);
    }

    public function down(): void
    {
        // Os valores anteriores não são guardados, então não há como restaurar.
    }
};
