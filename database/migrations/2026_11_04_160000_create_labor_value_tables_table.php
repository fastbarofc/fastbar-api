<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de valores dos freelancers (André, 2026-11-04): a mesma planilha "Tabela Atualizada Valores 2026",
     * editável na tela. Cada Salvar grava uma versão nova (data, quem salvou e o que mudou); a última é a que vale.
     */
    public function up(): void
    {
        Schema::create('labor_value_tables', function (Blueprint $table) {
            $table->id();
            $table->json('data');
            $table->json('changes')->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        $file = database_path('data/tabela_valores_2026.json');
        if (is_file($file)) {
            DB::table('labor_value_tables')->insert([
                'data' => file_get_contents($file),
                'changes' => json_encode([]),
                'note' => 'Tabela inicial (planilha Tabela Atualizada Valores 2026)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_value_tables');
    }
};
