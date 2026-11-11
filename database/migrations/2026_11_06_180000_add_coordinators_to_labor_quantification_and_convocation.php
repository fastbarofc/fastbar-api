<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Quantidade de coordenadores por setor (Quantificação) e convocado como coordenador (André 2026-11-06). */
    public function up(): void
    {
        if (!Schema::hasColumn('event_labor_quantifications', 'coordinator_quantity')) {
            Schema::table('event_labor_quantifications', function (Blueprint $table) {
                $table->unsignedInteger('coordinator_quantity')->default(0)->after('quantity');
            });
        }
        if (!Schema::hasColumn('event_labor_convocations', 'is_coordinator')) {
            Schema::table('event_labor_convocations', function (Blueprint $table) {
                $table->boolean('is_coordinator')->default(false)->after('collaborator_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_labor_quantifications', 'coordinator_quantity')) {
            Schema::table('event_labor_quantifications', fn (Blueprint $t) => $t->dropColumn('coordinator_quantity'));
        }
        if (Schema::hasColumn('event_labor_convocations', 'is_coordinator')) {
            Schema::table('event_labor_convocations', fn (Blueprint $t) => $t->dropColumn('is_coordinator'));
        }
    }
};
