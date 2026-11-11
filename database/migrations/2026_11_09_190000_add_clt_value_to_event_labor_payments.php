<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adicional CLT: R$ 30,00 a mais por evento para colaborador com carteira assinada (não vale em degustação, experiência e coffee break). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('event_labor_payments', 'clt_value')) {
            Schema::table('event_labor_payments', function (Blueprint $table) {
                $table->decimal('clt_value', 10, 2)->default(0)->after('coordination_value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_labor_payments', 'clt_value')) {
            Schema::table('event_labor_payments', fn (Blueprint $table) => $table->dropColumn('clt_value'));
        }
    }
};
