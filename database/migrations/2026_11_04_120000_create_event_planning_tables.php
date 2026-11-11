<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planejamento do evento:
     * - event_planning_steps: etapas do fluxograma marcadas como feitas (quem e quando).
     * - event_planning_items: custos previstos (terceirizado, mão de obra, hotel, lanche).
     *   Quando aprovados viram uma conta em Contas a pagar (payables), sem duplicar tabela de custo.
     */
    public function up(): void
    {
        Schema::create('event_planning_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('step_key', 40);
            $table->boolean('done')->default(false);
            $table->foreignId('done_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('done_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'step_key']);
        });

        Schema::create('event_planning_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('category', 20); // terceirizado, mao_de_obra, hotel, lanche
            $table->string('description');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('collaborator_id')->nullable()->constrained('collaborators')->onDelete('set null');
            $table->string('person_name')->nullable();
            $table->string('cpf', 20)->nullable();
            $table->string('role')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_value', 12, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('previsto'); // previsto, aprovado
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_planning_items');
        Schema::dropIfExists('event_planning_steps');
    }
};
