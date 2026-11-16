<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** André 2026-11-06: evento Concluído continua editável na Mão de Obra (regra desligada). */
    public function up(): void
    {
        // Em banco novo esta migration roda antes da 2026_11_10_300000 (que cria app_settings e pula se já existir).
        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        AppSetting::put('planning_rule.mao_de_obra_concluido_so_leitura', '0');
    }

    public function down(): void
    {
    }
};
