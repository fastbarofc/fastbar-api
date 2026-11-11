<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Desempenho: data em que o evento foi liberado para o Planejamento (lançada à mão, quando o sistema não tem). */
    public function up(): void
    {
        if (!Schema::hasColumn('event_planning_performances', 'planning_released_at')) {
            Schema::table('event_planning_performances', function (Blueprint $table) {
                $table->date('planning_released_at')->nullable()->after('event_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_planning_performances', 'planning_released_at')) {
            Schema::table('event_planning_performances', fn (Blueprint $table) => $table->dropColumn('planning_released_at'));
        }
    }
};
