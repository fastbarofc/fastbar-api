<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reuniões (vindas do Parthenon): cliente/parceiro/fornecedor no compromisso, link do Meet,
 * resumo feito pela IA e o áudio captado da reunião (guardado só até o resumo ficar pronto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('event_id')->constrained('clients')->nullOnDelete();
            }
            if (!Schema::hasColumn('appointments', 'partner_id')) {
                $table->foreignId('partner_id')->nullable()->after('client_id')->constrained('partners')->nullOnDelete();
            }
            if (!Schema::hasColumn('appointments', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('partner_id')->constrained('suppliers')->nullOnDelete();
            }
            if (!Schema::hasColumn('appointments', 'meeting_url')) {
                $table->string('meeting_url')->nullable()->after('location');
            }
            if (!Schema::hasColumn('appointments', 'google_event_id')) {
                $table->string('google_event_id')->nullable()->after('meeting_url');
            }
            if (!Schema::hasColumn('appointments', 'summary')) {
                $table->longText('summary')->nullable()->after('description');
                $table->string('summary_status')->nullable()->after('summary');
                $table->text('summary_error')->nullable()->after('summary_status');
            }
        });

        if (!Schema::hasTable('appointment_recordings')) {
            Schema::create('appointment_recordings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
                $table->string('path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('status')->default('recording');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_recordings');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('partner_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['meeting_url', 'google_event_id', 'summary', 'summary_status', 'summary_error']);
        });
    }
};
