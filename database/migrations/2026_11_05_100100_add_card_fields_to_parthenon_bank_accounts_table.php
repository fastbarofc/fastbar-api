<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contas Parthenon viram bancos, caixa e cartões de crédito (como no sistema da MAV):
 * nome, tipo, agência, saldo inicial e, para cartão, bandeira, final, dia de fechamento e vencimento.
 * Conta a pagar pode ser lançada num cartão (card_account_id): o vencimento segue a fatura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parthenon_bank_accounts', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('account_kind', 20)->default('banco')->after('name');
            $table->string('agency', 30)->nullable()->after('bank_name');
            $table->decimal('opening_balance', 12, 2)->default(0)->after('account_number');
            $table->date('opening_balance_date')->nullable()->after('opening_balance');
            $table->string('card_brand', 40)->nullable()->after('opening_balance_date');
            $table->string('card_last_digits', 8)->nullable()->after('card_brand');
            $table->unsignedTinyInteger('closing_day')->nullable()->after('card_last_digits');
            $table->unsignedTinyInteger('due_day')->nullable()->after('closing_day');
            $table->boolean('active')->default(true)->after('due_day');
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE parthenon_bank_accounts MODIFY account_number VARCHAR(255) NULL');
        }

        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('card_account_id')->nullable()->after('parthenon_bank_account_id')
                ->constrained('parthenon_bank_accounts')->nullOnDelete();
            $table->index(['card_account_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropIndex(['card_account_id', 'due_date']);
            $table->dropConstrainedForeignId('card_account_id');
        });
        Schema::table('parthenon_bank_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'account_kind', 'agency', 'opening_balance', 'opening_balance_date',
                'card_brand', 'card_last_digits', 'closing_day', 'due_day', 'active',
            ]);
        });
    }
};
