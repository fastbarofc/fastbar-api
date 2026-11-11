<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** André 2026-11-06: evento Concluído continua editável na Mão de Obra (regra desligada). */
    public function up(): void
    {
        AppSetting::put('planning_rule.mao_de_obra_concluido_so_leitura', '0');
    }

    public function down(): void
    {
    }
};
