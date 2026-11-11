<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Contas a receber: dados da baixa (valor recebido, conta que recebeu, observação e quem deu a baixa). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->decimal('received_amount', 10, 2)->nullable()->after('paid_at');
            $table->foreignId('parthenon_bank_account_id')->nullable()->after('payment_method_id')->constrained('parthenon_bank_accounts')->nullOnDelete();
            $table->foreignId('settled_by')->nullable()->after('parthenon_bank_account_id')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('settled_by');
        });
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parthenon_bank_account_id');
            $table->dropConstrainedForeignId('settled_by');
            $table->dropColumn(['received_amount', 'notes']);
        });
    }
};
