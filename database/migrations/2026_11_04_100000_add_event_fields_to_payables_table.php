<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contas a pagar do Financeiro: custo ligado ao evento (terceirizados, mão de obra, BV, notas...),
 * com centro de custo, quem recebe, aprovação e dados da baixa.
 * Operação financeira e o vínculo polimórfico antigo passam a ser opcionais.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('financial_operation_id')->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('cost_center_id')->constrained('suppliers')->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->after('supplier_id')->constrained('partners')->nullOnDelete();
            $table->foreignId('collaborator_id')->nullable()->after('partner_id')->constrained('collaborators')->nullOnDelete();
            $table->string('category', 30)->nullable()->after('description');
            $table->string('approval_status', 20)->default('approved')->after('status');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->decimal('paid_amount', 10, 2)->nullable()->after('paid_at');
            $table->foreignId('payment_method_id')->nullable()->after('paid_amount')->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('parthenon_bank_account_id')->nullable()->after('payment_method_id')->constrained('parthenon_bank_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payables MODIFY financial_operation_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE payables MODIFY payableable_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE payables MODIFY payableable_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            foreach (['event_id', 'cost_center_id', 'supplier_id', 'partner_id', 'collaborator_id', 'approved_by', 'payment_method_id', 'parthenon_bank_account_id'] as $fk) {
                $table->dropConstrainedForeignId($fk);
            }
            $table->dropColumn(['category', 'approval_status', 'approved_at', 'paid_amount', 'notes']);
        });
    }
};
