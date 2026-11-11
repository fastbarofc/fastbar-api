<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tabela de valores inicial do Planejamento (modelo trazido do Parthenon para a tela funcionar).
 * Os valores devem ser ajustados pelo Fastbar na própria tela; cada alteração gera nova versão.
 */
return new class extends Migration
{
    public function up(): void
    {
        $file = database_path('data/tabela_valores_2026.json');
        if (is_file($file) && !DB::table('labor_value_tables')->exists()) {
            DB::table('labor_value_tables')->insert([
                'data' => file_get_contents($file),
                'changes' => json_encode([]),
                'note' => 'Tabela inicial (modelo para ajustar)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
