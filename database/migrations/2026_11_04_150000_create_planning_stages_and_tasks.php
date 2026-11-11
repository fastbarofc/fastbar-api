<?php

use App\Models\EventPlanningStep;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etapas e tarefas do planejamento cadastráveis (Configurações > Planejamento), pedido do André 2026-11-04.
     * - planning_stages: etapas (grupos) do planejamento.
     * - planning_tasks: tarefas básicas de cada etapa (key liga com event_planning_steps.step_key).
     * - event_planning_steps ganha tarefas extras do evento (custom) e tarefas tiradas só daquele evento (removed).
     * Já carrega as 25 tarefas que estavam fixas no sistema.
     */
    public function up(): void
    {
        Schema::create('planning_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('planning_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_stage_id')->constrained('planning_stages')->onDelete('cascade');
            $table->string('key', 40)->unique();
            $table->string('label', 500);
            $table->string('who')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('event_planning_steps', function (Blueprint $table) {
            $table->foreignId('planning_stage_id')->nullable()->after('step_key')->constrained('planning_stages')->onDelete('cascade');
            $table->string('label', 500)->nullable()->after('planning_stage_id');
            $table->string('who')->nullable()->after('label');
            $table->boolean('custom')->default(false)->after('who');
            $table->boolean('removed')->default(false)->after('custom');
        });

        $now = now();
        $stageIds = [];
        $taskOrder = [];
        foreach (EventPlanningStep::STEPS as $step) {
            if (!isset($stageIds[$step['group']])) {
                $stageIds[$step['group']] = DB::table('planning_stages')->insertGetId([
                    'name' => $step['group'],
                    'sort_order' => count($stageIds) + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $stageId = $stageIds[$step['group']];
            $taskOrder[$stageId] = ($taskOrder[$stageId] ?? 0) + 1;
            DB::table('planning_tasks')->insert([
                'planning_stage_id' => $stageId,
                'key' => $step['key'],
                'label' => $step['label'],
                'who' => $step['who'],
                'sort_order' => $taskOrder[$stageId],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('event_planning_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planning_stage_id');
            $table->dropColumn(['label', 'who', 'custom', 'removed']);
        });
        Schema::dropIfExists('planning_tasks');
        Schema::dropIfExists('planning_stages');
    }
};
