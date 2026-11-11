<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setor tirado de um evento (botão ± Setores na Quantificação): a linha fica com excluded = 1
 * e as células desse setor somem só naquele evento. "Colocar setor" volta para 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('event_labor_quantifications', 'excluded')) {
            Schema::table('event_labor_quantifications', function (Blueprint $table) {
                $table->boolean('excluded')->default(false)->after('extra_lines');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_labor_quantifications', 'excluded')) {
            Schema::table('event_labor_quantifications', function (Blueprint $table) {
                $table->dropColumn('excluded');
            });
        }
    }
};
