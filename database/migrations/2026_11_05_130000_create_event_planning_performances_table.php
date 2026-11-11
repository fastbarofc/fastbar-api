<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Planejamento > Desempenho (planilha "Prazos de liberação" do André, 2026-11-05): ajustes manuais por evento. */
    public function up(): void
    {
        if (!Schema::hasTable('event_planning_performances')) {
            Schema::create('event_planning_performances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
                $table->date('released_at')->nullable();
                $table->boolean('staff')->default(false);
                $table->string('notes', 255)->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_planning_performances');
    }
};
