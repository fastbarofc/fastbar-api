<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presença dos convocados no evento (substitui a assinatura em papel do Relatório Financeiro):
 * confirmada pelo rosto (comparado com a foto do cadastro, no celular do coordenador) ou por assinatura com o dedo.
 * No colaborador fica guardado quando ele aceitou o uso da foto para isso (LGPD: dado biométrico, precisa de aceite).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('collaborators', 'biometric_consent_at')) {
            Schema::table('collaborators', function (Blueprint $table) {
                $table->dateTime('biometric_consent_at')->nullable()->after('employment_type');
                $table->unsignedBigInteger('biometric_consent_by')->nullable()->after('biometric_consent_at');
            });
        }

        if (!Schema::hasTable('event_labor_attendances')) {
            Schema::create('event_labor_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('collaborator_id')->constrained()->cascadeOnDelete();
                $table->string('method', 20); // face | signature
                $table->decimal('match_score', 5, 3)->nullable(); // semelhança do rosto (0 a 1)
                $table->string('signature_path')->nullable(); // imagem da assinatura com o dedo
                $table->dateTime('confirmed_at'); // hora no celular, mesmo se salvou depois (sem internet)
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['event_id', 'collaborator_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_labor_attendances');
        if (Schema::hasColumn('collaborators', 'biometric_consent_at')) {
            Schema::table('collaborators', fn (Blueprint $table) => $table->dropColumn(['biometric_consent_at', 'biometric_consent_by']));
        }
    }
};
