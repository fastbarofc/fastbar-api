<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table
                ->foreignId('post_event_consumption_report_file_id')
                ->nullable()
                ->after('contract_body')
                ->constrained('files');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['post_event_consumption_report_file_id']);
            $table->dropColumn('post_event_consumption_report_file_id');
        });
    }
};

