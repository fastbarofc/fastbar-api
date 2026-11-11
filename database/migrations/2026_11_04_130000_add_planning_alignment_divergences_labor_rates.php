<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planejamento, ajustes da gerente (2026-11-04):
     * - event_plannings: alinhamento com o cliente (campos próprios) + cidade e distância do evento.
     * - event_planning_divergences: divergências da conferência contrato x sistema (1.2).
     * - labor_rates: tabela de valores da mão de obra por cidade / faixa de distância / função.
     * - event_planning_items: origem (previsão gerada) e função.
     */
    public function up(): void
    {
        Schema::create('event_plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->onDelete('cascade');
            $table->string('city')->nullable();
            $table->decimal('distance_km', 8, 1)->nullable();
            $table->json('alignment')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('event_planning_divergences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->text('description');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('notified_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('labor_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_event_id')->nullable()->constrained('office_events')->onDelete('cascade');
            $table->string('role')->nullable();
            $table->string('city')->nullable();
            $table->decimal('km_from', 8, 1)->nullable();
            $table->decimal('km_to', 8, 1)->nullable();
            $table->decimal('daily_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('event_planning_items', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('category'); // previsao = gerada pela escala
            $table->foreignId('office_event_id')->nullable()->after('source')->constrained('office_events')->onDelete('set null');
        });

        if (Schema::hasTable('notification_types') && !DB::table('notification_types')->where('name', 'planning_alert')->exists()) {
            DB::table('notification_types')->insert([
                'name' => 'planning_alert',
                'description' => 'Aviso do Planejamento',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('event_planning_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_event_id');
            $table->dropColumn('source');
        });
        Schema::dropIfExists('labor_rates');
        Schema::dropIfExists('event_planning_divergences');
        Schema::dropIfExists('event_plannings');
        DB::table('notification_types')->where('name', 'planning_alert')->delete();
    }
};
