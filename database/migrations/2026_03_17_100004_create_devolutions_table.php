<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('registered_by_id')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolutions');
    }
};
