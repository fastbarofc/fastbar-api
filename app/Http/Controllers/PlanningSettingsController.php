<?php

namespace App\Http\Controllers;

use App\Models\EventPlanningStep;
use App\Models\PlanningStage;
use App\Models\PlanningTask;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Configurações > Planejamento > Etapas do planejamento (André, 2026-11-04):
 * cadastro das etapas e das tarefas básicas de cada etapa, que aparecem em todos os eventos.
 */
class PlanningSettingsController extends Controller
{
    public function index()
    {
        $stages = PlanningStage::with('tasks')->orderBy('sort_order')->orderBy('id')->get();
        return response()->json(['data' => $stages]);
    }

    public function storeStage(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $data['sort_order'] = (int) PlanningStage::max('sort_order') + 1;
        return response()->json(['data' => PlanningStage::create($data)], 201);
    }

    public function updateStage(Request $request, PlanningStage $planningStage)
    {
        $planningStage->update($request->validate(['name' => 'required|string|max:255']));
        return response()->json(['data' => $planningStage]);
    }

    public function destroyStage(PlanningStage $planningStage)
    {
        if ($planningStage->tasks()->exists()) {
            return response()->json(['message' => 'Esta etapa ainda tem tarefas. Exclua ou mude as tarefas de etapa antes.'], 422);
        }
        EventPlanningStep::where('planning_stage_id', $planningStage->id)->delete();
        $planningStage->delete();
        return response()->json(null, 204);
    }

    public function moveStage(Request $request, PlanningStage $planningStage)
    {
        $this->move(PlanningStage::query(), $planningStage, $request->input('direction'));
        return $this->index();
    }

    public function storeTask(Request $request)
    {
        $data = $this->validateTask($request);
        $data['key'] = 'tarefa_' . Str::lower(Str::random(12));
        $data['sort_order'] = (int) PlanningTask::where('planning_stage_id', $data['planning_stage_id'])->max('sort_order') + 1;
        return response()->json(['data' => PlanningTask::create($data)], 201);
    }

    public function updateTask(Request $request, PlanningTask $planningTask)
    {
        $data = $this->validateTask($request);
        if ((int) $data['planning_stage_id'] !== (int) $planningTask->planning_stage_id) {
            $data['sort_order'] = (int) PlanningTask::where('planning_stage_id', $data['planning_stage_id'])->max('sort_order') + 1;
        }
        $planningTask->update($data);
        return response()->json(['data' => $planningTask]);
    }

    /** Sai do cadastro (não aparece mais nos eventos). O que já foi marcado nos eventos fica guardado. */
    public function destroyTask(PlanningTask $planningTask)
    {
        $planningTask->delete();
        return response()->json(null, 204);
    }

    public function moveTask(Request $request, PlanningTask $planningTask)
    {
        $this->move(PlanningTask::where('planning_stage_id', $planningTask->planning_stage_id), $planningTask, $request->input('direction'));
        return $this->index();
    }

    private function validateTask(Request $request): array
    {
        return $request->validate([
            'planning_stage_id' => 'required|exists:planning_stages,id',
            'label' => 'required|string|max:500',
            'who' => 'nullable|string|max:255',
        ]);
    }

    /** Sobe ou desce uma posição (renumera a lista toda para não ter empate). */
    private function move($query, $model, ?string $direction): void
    {
        $list = $query->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $list->search(fn ($m) => $m->id === $model->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index === false || $target < 0 || $target >= $list->count()) {
            return;
        }
        $items = $list->all();
        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        foreach ($items as $i => $m) {
            if ($m->sort_order !== $i + 1) {
                $m->update(['sort_order' => $i + 1]);
            }
        }
    }
}
