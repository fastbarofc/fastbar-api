<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enum to include both old and new values so data can migrate safely
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending', 'received', 'cancelled', 'financial_completed', 'invoice_received', 'paid') DEFAULT 'pending'");

        // Migrate existing 'financial_completed' rows to the new 'paid' status
        DB::table('purchase_orders')
            ->where('status', 'financial_completed')
            ->update(['status' => 'paid']);

        // Shrink enum to the final set
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending', 'received', 'invoice_received', 'paid', 'cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending', 'received', 'cancelled', 'financial_completed', 'invoice_received', 'paid') DEFAULT 'pending'");

        DB::table('purchase_orders')
            ->whereIn('status', ['invoice_received', 'paid'])
            ->update(['status' => 'financial_completed']);

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending', 'received', 'cancelled', 'financial_completed') DEFAULT 'pending'");
    }
};
