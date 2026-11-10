<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Histórico das planilhas importadas em Colaboradores (cadastro e lista CLT).
     */
    public function up(): void
    {
        Schema::create('collaborator_imports', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // cadastro | clt
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborator_imports');
    }
};
