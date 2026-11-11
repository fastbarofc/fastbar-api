<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anexo do mapa de mesas (layout do evento) no Alinhamento com o cliente. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_plannings', function (Blueprint $table) {
            $table->foreignId('table_map_file_id')->nullable()->after('alignment')->constrained('files')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('event_plannings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('table_map_file_id');
        });
    }
};
