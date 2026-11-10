<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aceite do uso da foto para confirmar presença nos eventos (LGPD).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('collaborators', 'biometric_consent_at')) {
            Schema::table('collaborators', function (Blueprint $table) {
                $table->dateTime('biometric_consent_at')->nullable()->after('employment_type');
                $table->unsignedBigInteger('biometric_consent_by')->nullable()->after('biometric_consent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('collaborators', 'biometric_consent_at')) {
            Schema::table('collaborators', fn (Blueprint $table) => $table->dropColumn(['biometric_consent_at', 'biometric_consent_by']));
        }
    }
};
