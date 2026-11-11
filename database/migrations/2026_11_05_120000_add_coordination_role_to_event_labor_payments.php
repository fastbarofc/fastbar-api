<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Coordenação de quem recebe o adicional (linha da parte COORDENAÇÕES da Tabela de valores). André 2026-11-05. */
    public function up(): void
    {
        if (!Schema::hasColumn('event_labor_payments', 'coordination_role')) {
            Schema::table('event_labor_payments', function (Blueprint $table) {
                $table->string('coordination_role', 80)->nullable()->after('coordination_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_labor_payments', 'coordination_role')) {
            Schema::table('event_labor_payments', fn (Blueprint $table) => $table->dropColumn('coordination_role'));
        }
    }
};
