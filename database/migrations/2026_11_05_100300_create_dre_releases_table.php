<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liberação do DRE do mês (como no sistema da MAV): enquanto o mês não é liberado,
 * o DRE e o ARM daquele mês são provisórios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dre_releases', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique(); // dia 1 do mês
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('released_by_name')->nullable();
            $table->timestamp('released_at');
            $table->boolean('had_pending')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dre_releases');
    }
};
