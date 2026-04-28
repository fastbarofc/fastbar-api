<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_product', function (Blueprint $table) {
            $table->boolean('ordered')->default(false)->after('quantity');
        });

        DB::statement("
            UPDATE purchase_request_product prp
            INNER JOIN purchase_order_product pop
                ON pop.purchase_request_id = prp.purchase_request_id
                AND pop.product_id = prp.product_id
            INNER JOIN purchase_orders po
                ON po.id = pop.purchase_order_id
                AND po.deleted_at IS NULL
                AND po.status != 'cancelled'
            SET prp.ordered = 1
        ");
    }

    public function down(): void
    {
        Schema::table('purchase_request_product', function (Blueprint $table) {
            $table->dropColumn('ordered');
        });
    }
};
