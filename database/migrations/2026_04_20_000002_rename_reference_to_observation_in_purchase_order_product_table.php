<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_product', function (Blueprint $table) {
            $table->renameColumn('reference', 'observation');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_product', function (Blueprint $table) {
            $table->renameColumn('observation', 'reference');
        });
    }
};
