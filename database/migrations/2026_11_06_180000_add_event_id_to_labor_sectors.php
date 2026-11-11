<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setor criado dentro de um evento (ex.: Cerimonial só no evento 38): event_id preenchido.
 * Setores da base (Configurações > Planejamento > Setores de mão de obra) ficam com event_id nulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('labor_sectors', 'event_id')) {
            Schema::table('labor_sectors', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('labor_sectors', 'event_id')) {
            Schema::table('labor_sectors', function (Blueprint $table) {
                $table->dropConstrainedForeignId('event_id');
            });
        }
    }
};
