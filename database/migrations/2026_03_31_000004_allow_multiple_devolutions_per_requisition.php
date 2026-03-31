<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devolutions', function (Blueprint $table) {
            $table->dropForeign(['requisition_id']);
            $table->dropUnique(['requisition_id']);
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('devolutions', function (Blueprint $table) {
            $table->dropForeign(['requisition_id']);
            $table->unique('requisition_id');
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
        });
    }
};
