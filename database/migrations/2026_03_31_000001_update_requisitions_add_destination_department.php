<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            // Torna event_id nullable (requisição pode ser para departamento)
            $table->dropForeign(['event_id']);
            $table->unsignedBigInteger('event_id')->nullable()->change();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');

            // Departamento destino para requisições departamento→departamento
            $table->foreignId('destination_department_id')
                ->nullable()
                ->after('department_id')
                ->constrained('departments')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['destination_department_id']);
            $table->dropColumn('destination_department_id');

            $table->dropForeign(['event_id']);
            $table->unsignedBigInteger('event_id')->nullable(false)->change();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });
    }
};
