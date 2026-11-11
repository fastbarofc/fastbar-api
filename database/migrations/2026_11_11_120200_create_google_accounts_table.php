<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conta Google de cada usuário (botão "Conectar Google" em Minha conta).
 * Serve para o sistema criar o link do Google Meet sozinho, em nome da pessoa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('google_accounts')) {
            Schema::create('google_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('email')->nullable();
                $table->string('name')->nullable();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('appointments', 'google_event_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('google_event_id')->nullable()->after('meeting_url');
            });
        }
    }

    public function down(): void
    {
        // google_event_id sai junto com 2026_11_11_120000_add_meeting_fields_to_appointments
        Schema::dropIfExists('google_accounts');
    }
};
