<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link do evento para o coordenador confirmar presença pelo celular sem login (André, 2026-11-09).
 * Abre só a tela de presença daquele evento, vale só entre as datas escolhidas e pode ser cancelado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_attendance_links')) {
            Schema::create('event_attendance_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('token', 64)->unique();
                $table->date('valid_from');
                $table->date('valid_until');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance_links');
    }
};
