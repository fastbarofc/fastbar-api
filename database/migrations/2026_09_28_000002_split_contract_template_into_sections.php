<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O modelo passa a ser único (Fast Bar) e dividido em trechos; o HTML
        // inteiro de cada empresa não tem como ser aproveitado nesse formato.
        DB::table('contract_templates')->delete();

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('content');
            $table->json('sections')->nullable()->after('id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->json('contract_sections')->nullable()->after('contract_body');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('contract_sections');
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('sections');
            $table->text('content')->nullable()->after('id');
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
        });
    }
};
