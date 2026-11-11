<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setores eventuais (ex.: Cerimonial): cadastrados na Mão de Obra pelo "+ Setor".
 * Só aparecem como coluna na Quantificação nos meses em que algum evento usa o setor
 * (ou quando o usuário escolhe mostrar o setor para preencher).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('labor_sectors', 'occasional')) {
            Schema::table('labor_sectors', function (Blueprint $table) {
                $table->boolean('occasional')->default(false)->after('active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('labor_sectors', 'occasional')) {
            Schema::table('labor_sectors', function (Blueprint $table) {
                $table->dropColumn('occasional');
            });
        }
    }
};
