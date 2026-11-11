<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conciliação bancária (como no sistema da MAV): extratos importados (OFX/CSV) e suas linhas.
 * Cada linha do extrato fica pendente, conciliada com uma conta a receber/pagar já baixada, ou ignorada.
 * Faturas do cartão de crédito importadas: cada linha é vinculada a uma conta a pagar, lançada ou ignorada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parthenon_bank_account_id')->constrained('parthenon_bank_accounts')->cascadeOnDelete();
            $table->string('file_name')->nullable();
            $table->string('file_format', 10);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('closing_balance', 12, 2)->nullable();
            $table->date('closing_balance_date')->nullable();
            $table->unsignedInteger('lines_total')->default(0);
            $table->unsignedInteger('lines_new')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['parthenon_bank_account_id', 'period_end'], 'bsi_account_period_idx');
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('bank_statement_imports')->cascadeOnDelete();
            $table->foreignId('parthenon_bank_account_id')->constrained('parthenon_bank_accounts')->cascadeOnDelete();
            $table->date('txn_date');
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->string('doc_key');
            $table->string('status', 20)->default('pendente'); // pendente, conciliado, ignorado
            $table->string('matched_ref', 60)->nullable(); // receivable:ID, payable:ID ou card:ID:AAAA-MM-DD (fatura)
            $table->timestamps();
            $table->unique(['parthenon_bank_account_id', 'doc_key'], 'bsl_account_doc_unique');
            $table->index(['parthenon_bank_account_id', 'txn_date'], 'bsl_account_date_idx');
            $table->index('matched_ref', 'bsl_matched_idx');
        });

        Schema::create('card_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_account_id')->constrained('parthenon_bank_accounts')->cascadeOnDelete();
            $table->date('due_date');
            $table->date('txn_date');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->unsignedSmallInteger('installment_total')->nullable();
            $table->string('doc_key');
            $table->string('file_name')->nullable();
            $table->string('status', 20)->default('pendente'); // pendente, vinculado, lancado, ignorado
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['card_account_id', 'due_date', 'doc_key'], 'cil_card_due_doc_unique');
            $table->index(['card_account_id', 'due_date'], 'cil_card_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_invoice_lines');
        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statement_imports');
    }
};
