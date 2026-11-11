<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Relatório Financeiro: linhas a mais em branco por setor (botão "+ Linha"), além da quantidade da Quantificação. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('event_labor_quantifications', 'extra_lines')) {
            return;
        }
        Schema::table('event_labor_quantifications', function (Blueprint $table) {
            $table->unsignedInteger('extra_lines')->default(0)->after('transport_quantity');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_labor_quantifications', 'extra_lines')) {
            Schema::table('event_labor_quantifications', function (Blueprint $table) {
                $table->dropColumn('extra_lines');
            });
        }
    }
};
