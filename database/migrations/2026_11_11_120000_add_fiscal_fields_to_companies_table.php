<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emissor de nota fiscal pela Focus NFe (André, 2026-11-11): dados da empresa que emite a nota
 * (prestador). CNPJ, inscrição municipal e cidade são exigidos pela prefeitura; o resto preenche o serviço.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'cnpj')) {
                $table->string('cnpj', 14)->nullable()->after('trade_name');
            }
            if (!Schema::hasColumn('companies', 'municipal_registration')) {
                $table->string('municipal_registration', 50)->nullable()->after('cnpj');
            }
            $table->foreignId('city_id')->nullable()->after('municipal_registration')->constrained('cities')->nullOnDelete();
            if (!Schema::hasColumn('companies', 'tax_regime')) {
                $table->string('tax_regime', 30)->nullable()->after('type'); // simples_nacional, mei, lucro_presumido, lucro_real
            }
            if (!Schema::hasColumn('companies', 'nf_environment')) {
                $table->string('nf_environment', 20)->default('homologacao')->after('tax_regime'); // homologacao, producao
            }
            if (!Schema::hasColumn('companies', 'nf_service_code')) {
                $table->string('nf_service_code', 30)->nullable()->after('nf_environment'); // item da lista de serviço (LC 116)
            }
            $table->string('nf_municipal_tax_code', 30)->nullable()->after('nf_service_code'); // código tributário do município
            if (!Schema::hasColumn('companies', 'nf_iss_rate')) {
                $table->decimal('nf_iss_rate', 5, 2)->nullable()->after('nf_municipal_tax_code');
            }
            $table->text('nf_default_description')->nullable()->after('nf_iss_rate');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['cnpj', 'municipal_registration', 'tax_regime', 'nf_environment', 'nf_service_code', 'nf_municipal_tax_code', 'nf_iss_rate', 'nf_default_description']);
        });
    }
};
