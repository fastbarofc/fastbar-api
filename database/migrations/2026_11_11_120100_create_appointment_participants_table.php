<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Participantes da reunião: vários clientes, parceiros, fornecedores e colaboradores na mesma reunião.
 * A reunião aparece no cadastro de cada participante.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointment_participants')) {
            Schema::create('appointment_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
                $table->string('participant_type', 20); // client, partner, supplier, collaborator
                $table->unsignedBigInteger('participant_id');
                $table->unique(['appointment_id', 'participant_type', 'participant_id'], 'appointment_participants_unique');
                $table->index(['participant_type', 'participant_id']);
            });
        }

        // Reuniões já criadas com um cliente, parceiro ou fornecedor passam para a lista de participantes.
        foreach (['client' => 'client_id', 'partner' => 'partner_id', 'supplier' => 'supplier_id'] as $type => $column) {
            if (!Schema::hasColumn('appointments', $column)) {
                continue;
            }
            $rows = DB::table('appointments')->whereNotNull($column)->get(['id', $column]);
            foreach ($rows as $row) {
                DB::table('appointment_participants')->insertOrIgnore([
                    'appointment_id' => $row->id,
                    'participant_type' => $type,
                    'participant_id' => $row->{$column},
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_participants');
    }
};
