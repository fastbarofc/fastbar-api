<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Divergência avisa o responsável do evento e o gerente comercial (André 2026-11-06): nomes de quem recebeu. */
    public function up(): void
    {
        if (!Schema::hasColumn('event_planning_divergences', 'notified_names')) {
            Schema::table('event_planning_divergences', function (Blueprint $table) {
                $table->string('notified_names', 255)->nullable()->after('notified_user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_planning_divergences', 'notified_names')) {
            Schema::table('event_planning_divergences', fn (Blueprint $table) => $table->dropColumn('notified_names'));
        }
    }
};
