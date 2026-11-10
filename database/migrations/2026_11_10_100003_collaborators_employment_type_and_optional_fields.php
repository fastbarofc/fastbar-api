<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vínculo do colaborador (CLT ou Freelancer), tipo de conta bancária
     * e Setor/Cargo/Celular opcionais (cadastros importados da planilha).
     */
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->string('employment_type', 20)->default('freelancer')->after('sector_office_id');
        });

        DB::statement('ALTER TABLE collaborators MODIFY phone_1 VARCHAR(255) NULL');
        DB::statement('ALTER TABLE collaborators MODIFY sector_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE collaborators MODIFY sector_office_id BIGINT UNSIGNED NULL');

        Schema::table('bank_details', function (Blueprint $table) {
            $table->string('account_type', 30)->nullable()->after('account');
        });
    }

    public function down(): void
    {
        Schema::table('bank_details', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });

        Schema::table('collaborators', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });
    }
};
