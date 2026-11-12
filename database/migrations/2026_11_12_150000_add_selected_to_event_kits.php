<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menu de drinks do evento no formato dos itens de buffet do Parthenon:
     * caixinha "Básico" (selected) e item digitado direto na pasta do evento (is_item).
     * O que já existe continua marcado (padrão true).
     */
    public function up(): void
    {
        Schema::table('event_kits', function (Blueprint $table) {
            $table->boolean('selected')->default(true)->after('checklist');
            $table->boolean('is_item')->default(false)->after('selected');
        });

        Schema::table('event_kit_product', function (Blueprint $table) {
            $table->boolean('selected')->default(true)->after('checklist');
        });
    }

    public function down(): void
    {
        Schema::table('event_kits', function (Blueprint $table) {
            $table->dropColumn(['selected', 'is_item']);
        });

        Schema::table('event_kit_product', function (Blueprint $table) {
            $table->dropColumn('selected');
        });
    }
};
