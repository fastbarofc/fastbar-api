<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventPlanning;
use App\Models\EventPlanningDivergence;
use App\Models\EventPlanningItem;
use App\Models\EventPlanningStep;
use App\Models\LaborRate;
use App\Models\Payable;
use App\Models\PlanningStage;
use App\Models\PlanningTask;
use App\Models\File;
use App\Services\FileService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Setor Planejamento: etapas do evento (checklist do fluxograma) e custos previstos
 * (terceirizados, mão de obra, hotel, lanche). Custo aprovado vira conta em Contas a pagar.
 */
class EventPlanningController extends Controller
{
    /** Status que entram no Planejamento (evento já fechado). */
    private const STATUSES = ['Fechado', 'Planejamento', 'Concluído'];

    /** Lista do mês: eventos fechados com o andamento do planejamento. */
    public function events(Request $request)
    {
        $month = $request->input('month') ?: now()->format('Y-m');
        try {
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
        }
        $end = (clone $start)->endOfMonth();

        $events = Event::query()->light()
            ->with(['client:id,name', 'eventPlace:id,name', 'eventStatus:id,name'])
            ->whereHas('eventStatus', fn ($q) => $q->whereIn('name', self::STATUSES))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $ids = $events->pluck('id');
        $stages = $this->stages();
        $rowsByEvent = EventPlanningStep::whereIn('event_id', $ids)->get()->groupBy('event_id');
        $costs = EventPlanningItem::whereIn('event_id', $ids)
            ->select('event_id', 'status', DB::raw('sum(total_value) as total'))
            ->groupBy('event_id', 'status')->get()->groupBy('event_id');

        $today = now()->startOfDay();
        $data = $events->map(function (Event $event) use ($stages, $rowsByEvent, $costs, $today) {
            $steps = $this->buildSteps($stages, ($rowsByEvent[$event->id] ?? collect())->keyBy('step_key'));
            $next = $steps->first(fn ($s) => !$s['done']);
            $eventCosts = $costs[$event->id] ?? collect();
            $date = $event->date ? Carbon::parse($event->date)->startOfDay() : null;
            return [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date?->format('Y-m-d'),
                'time' => $event->time,
                'client' => $event->client?->name,
                'place' => $event->eventPlace?->name,
                'status' => $event->eventStatus?->name,
                'guests' => $event->event_guests,
                'present_guests' => $event->present_guests,
                'days_until' => $date ? $today->diffInDays($date, false) : null,
                'steps_done' => $steps->where('done', true)->count(),
                'steps_total' => $steps->count(),
                'next_step' => $next['label'] ?? null,
                'cost_planned' => (float) $eventCosts->where('status', 'previsto')->sum('total'),
                'cost_approved' => (float) $eventCosts->where('status', 'aprovado')->sum('total'),
            ];
        })->values();

        return response()->json(['data' => $data, 'month' => $start->format('Y-m')]);
    }

    /** Planejamento de um evento: etapas e custos. */
    public function show(Event $event)
    {
        return response()->json(['data' => $this->payload($event)]);
    }

    public function updateStep(Request $request, Event $event, string $stepKey)
    {
        $exists = PlanningTask::where('key', $stepKey)->exists()
            || EventPlanningStep::where('event_id', $event->id)->where('step_key', $stepKey)->where('custom', true)->exists();
        abort_unless($exists, 404, 'Tarefa não encontrada.');
        $data = $request->validate([
            'done' => 'sometimes|boolean',
            'note' => 'nullable|string|max:2000',
        ]);

        $step = EventPlanningStep::firstOrNew(['event_id' => $event->id, 'step_key' => $stepKey]);
        if (array_key_exists('done', $data)) {
            $step->done = (bool) $data['done'];
            $step->done_by = $step->done ? $request->user()?->id : null;
            $step->done_at = $step->done ? now() : null;
        }
        if (array_key_exists('note', $data)) {
            $step->note = $data['note'];
        }
        $step->save();

        return response()->json(['data' => $this->payload($event)]);
    }

    public function storeItem(Request $request, Event $event)
    {
        $data = $this->validateItem($request);
        $data['event_id'] = $event->id;
        $data['user_id'] = $request->user()?->id;
        $data['status'] = 'previsto';
        $data['total_value'] = round(($data['quantity'] ?? 1) * ($data['unit_value'] ?? 0), 2);
        EventPlanningItem::create($data);

        return response()->json(['data' => $this->payload($event)], 201);
    }

    public function updateItem(Request $request, EventPlanningItem $eventPlanningItem)
    {
        abort_if($eventPlanningItem->status === 'aprovado', 422, 'Este custo já foi aprovado e está em Contas a pagar. Desfaça a aprovação para editar.');
        $data = $this->validateItem($request);
        $data['total_value'] = round(($data['quantity'] ?? 1) * ($data['unit_value'] ?? 0), 2);
        $eventPlanningItem->update($data);

        return response()->json(['data' => $this->payload($eventPlanningItem->event)]);
    }

    public function destroyItem(EventPlanningItem $eventPlanningItem)
    {
        abort_if($eventPlanningItem->status === 'aprovado', 422, 'Este custo já foi aprovado e está em Contas a pagar. Desfaça a aprovação para excluir.');
        $event = $eventPlanningItem->event;
        $eventPlanningItem->delete();

        return response()->json(['data' => $this->payload($event)]);
    }

    /** Aprova o custo: cria a conta em Contas a pagar ligada ao evento. */
    public function approveItem(Request $request, EventPlanningItem $eventPlanningItem)
    {
        $item = $eventPlanningItem->load('event');
        // Aprovado antes de Contas a pagar existir (sem conta ligada): aprovar de novo lança a conta.
        if ($item->status !== 'aprovado' || !$item->payable_id) {
            DB::transaction(function () use ($item, $request) {
                $payable = $this->createPayable($item);
                $item->update([
                    'status' => 'aprovado',
                    'approved_by' => $item->approved_by ?? $request->user()?->id,
                    'approved_at' => $item->approved_at ?? now(),
                    'payable_id' => $payable?->id,
                ]);
            });
        }

        return response()->json(['data' => $this->payload($item->event)]);
    }

    /** Desfaz a aprovação (só se a conta ainda não foi paga). */
    public function unapproveItem(EventPlanningItem $eventPlanningItem)
    {
        $item = $eventPlanningItem->load('event');
        $payable = $item->payable_id ? Payable::find($item->payable_id) : null;
        abort_if($payable && ($payable->status === 'paid' || $payable->paid_at), 422, 'Esta conta já foi paga no Financeiro; não dá para desfazer.');

        DB::transaction(function () use ($item, $payable) {
            $payable?->delete();
            $item->update(['status' => 'previsto', 'approved_by' => null, 'approved_at' => null, 'payable_id' => null]);
        });

        return response()->json(['data' => $this->payload($item->event)]);
    }

    /** Alinhamento com o cliente + cidade e distância do evento. */
    public function updateInfo(Request $request, Event $event)
    {
        $data = $request->validate([
            'city' => 'nullable|string|max:255',
            'distance_km' => 'nullable|numeric|min:0',
            'alignment' => 'nullable|array',
            'alignment.*' => 'nullable|string|max:5000',
        ]);
        $alignment = array_intersect_key($data['alignment'] ?? [], EventPlanning::ALIGNMENT_FIELDS);
        EventPlanning::updateOrCreate(['event_id' => $event->id], [
            'city' => $data['city'] ?? null,
            'distance_km' => $data['distance_km'] ?? null,
            'alignment' => $alignment,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $this->payload($event)]);
    }

    /** Anexa o mapa de mesas (layout do evento): PDF, JPG ou PNG em base64. Substitui o anterior. */
    public function storeTableMap(Request $request, Event $event)
    {
        $request->validate(
            [
                'data' => ['required', 'string', 'regex:/^data:(image\/(png|jpe?g)|application\/pdf);base64,/'],
                'name' => 'nullable|string|max:255',
            ],
            ['data.required' => 'Envie o arquivo.', 'data.regex' => 'O mapa deve ser um PDF, JPG ou PNG.']
        );

        $planning = EventPlanning::firstOrCreate(['event_id' => $event->id]);
        $old = $planning->tableMapFile;
        $file = FileService::saveBase64File('table-maps', $request->input('data'), $request->input('name'));
        $planning->update(['table_map_file_id' => $file->id, 'updated_by' => $request->user()?->id]);
        if ($old) {
            FileService::delete($old);
        }

        return response()->json(['data' => $this->payload($event)], 201);
    }

    /** Remove o mapa de mesas anexado. */
    public function destroyTableMap(Request $request, Event $event)
    {
        $planning = EventPlanning::where('event_id', $event->id)->first();
        $file = $planning?->tableMapFile;
        if ($planning && $file) {
            $planning->update(['table_map_file_id' => null, 'updated_by' => $request->user()?->id]);
            FileService::delete($file);
        }

        return response()->json(['data' => $this->payload($event)]);
    }

    /** Registra uma divergência da conferência e avisa o consultor do evento. */
    public function storeDivergence(Request $request, Event $event, NotificationService $notifications)
    {
        $data = $request->validate(['description' => 'required|string|max:5000']);
        $consultant = $event->collaborator?->user;
        // Aviso vai para o responsável do evento e para o(s) gerente(s) comercial(is) (André 2026-11-06).
        $recipients = collect([$consultant])->merge(self::commercialManagers())->filter()->unique('id')->values();

        $divergence = EventPlanningDivergence::create([
            'event_id' => $event->id,
            'description' => $data['description'],
            'user_id' => $request->user()?->id,
            'notified_user_id' => $consultant?->id,
            'notified_names' => mb_substr($recipients->pluck('name')->implode(', '), 0, 255) ?: null,
        ]);

        // Divergência aberta: a conferência precisa ser feita de novo.
        foreach (['conferir_contrato', 'divergencias'] as $key) {
            EventPlanningStep::updateOrCreate(['event_id' => $event->id, 'step_key' => $key], ['done' => false, 'done_by' => null, 'done_at' => null]);
        }

        foreach ($recipients as $user) {
            try {
                $notifications->create(
                    'planning_alert',
                    $user,
                    "Divergência no evento {$event->id}",
                    "O Planejamento encontrou uma divergência no evento {$event->id} - {$event->name}: {$data['description']}",
                    ['event_id' => $event->id, 'url' => "/admin/events/form/{$event->id}?tab=Planejamento"],
                    $divergence,
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['data' => $this->payload($event)], 201);
    }

    /** Gerente comercial = usuário com o perfil "Gerente Comercial" ou colaborador com o cargo "Gerente Comercial". */
    public static function commercialManagers()
    {
        $byRole = \App\Models\User::whereHas('roles', fn ($q) => $q->where('name', 'Gerente Comercial'))->get();
        $byOffice = \App\Models\User::whereHas('collaborator.sectorOffice', fn ($q) => $q->whereRaw('TRIM(name) = ?', ['Gerente Comercial']))->get();

        return $byRole->merge($byOffice)->unique('id')->values();
    }

    public function resolveDivergence(Request $request, EventPlanningDivergence $eventPlanningDivergence)
    {
        $eventPlanningDivergence->update(['resolved_at' => now(), 'resolved_by' => $request->user()?->id]);
        $event = Event::find($eventPlanningDivergence->event_id);

        return response()->json(['data' => $this->payload($event)]);
    }

    /**
     * Gera a previsão da mão de obra pela escala (Colaboradores do Evento): uma linha por função
     * com a quantidade e o valor da tabela. Substitui a previsão gerada antes que ainda não foi aprovada.
     */
    /**
     * Previsão de mão de obra a partir da Quantificação (Planejamento > Mão de Obra): uma linha por setor
     * do evento, quantidade = Qtd. + Coord., diária pela linha do setor na Tabela de valores (cidade do local).
     * A aba "Colaboradores do Evento" saiu da tela (2026-11-06), por isso a fonte passou a ser a Quantificação.
     */
    public function laborForecast(Request $request, Event $event)
    {
        $planning = EventPlanning::where('event_id', $event->id)->first();
        $rows = \App\Models\EventLaborQuantification::with('sector')->where('event_id', $event->id)->get()
            ->filter(fn ($q) => (float) $q->quantity + (int) $q->coordinator_quantity > 0);
        abort_if($rows->isEmpty(), 422, 'Preencha a Quantificação do evento (Planejamento > Mão de Obra) antes de gerar a previsão.');

        $missing = [];
        $valueTable = \App\Models\LaborValueTable::current();
        $city = $planning?->city ?: $event->eventPlace?->address?->city?->name;
        $hours = $event->duration !== null ? (float) $event->duration : null;
        DB::transaction(function () use ($event, $rows, $planning, $request, &$missing, $valueTable, $city, $hours) {
            EventPlanningItem::where('event_id', $event->id)->where('source', 'previsao')->where('status', 'previsto')->delete();
            $approved = EventPlanningItem::where('event_id', $event->id)->where('source', 'previsao')->where('status', 'aprovado')->pluck('role')->all();

            foreach ($rows as $q) {
                $name = $q->sector?->name ?? 'Setor';
                if (in_array($name, $approved, true)) {
                    continue;
                }
                $role = $q->sector?->has_truck ? \App\Http\Controllers\LaborConvocationController::sectorRole($q->sector, $q) : ($q->sector?->value_table_role ?: 'GARÇOM');
                $value = $role ? $valueTable?->valueFor($role, $city, $planning?->distance_km, $hours) : null;
                if ($value === null) {
                    $missing[] = $name;
                }
                $qty = (float) $q->quantity + (int) $q->coordinator_quantity;
                $value = (float) ($value ?? 0);
                EventPlanningItem::create([
                    'event_id' => $event->id,
                    'category' => 'mao_de_obra',
                    'source' => 'previsao',
                    'description' => "Previsão: {$name}",
                    'role' => $name,
                    'quantity' => $qty,
                    'unit_value' => $value,
                    'total_value' => round($qty * $value, 2),
                    'status' => 'previsto',
                    'user_id' => $request->user()?->id,
                ]);
            }
        });

        return response()->json(['data' => $this->payload($event), 'missing_rates' => $missing]);
    }

    // ---- Tabela de valores da mão de obra ----

    public function laborRates()
    {
        $rates = LaborRate::with('officeEvent:id,description')->get()
            ->sortBy(fn ($r) => [$r->officeEvent?->description ?? $r->role, $r->city ?? '', $r->km_from ?? -1])
            ->values()
            ->map(fn (LaborRate $r) => [
                'id' => $r->id,
                'office_event_id' => $r->office_event_id,
                'role' => $r->officeEvent?->description ?? $r->role,
                'city' => $r->city,
                'km_from' => $r->km_from,
                'km_to' => $r->km_to,
                'daily_value' => $r->daily_value,
                'notes' => $r->notes,
            ]);

        return response()->json(['data' => $rates]);
    }

    public function storeLaborRate(Request $request)
    {
        $rate = LaborRate::create($this->validateRate($request));
        return response()->json(['data' => $rate], 201);
    }

    public function updateLaborRate(Request $request, LaborRate $laborRate)
    {
        $laborRate->update($this->validateRate($request));
        return response()->json(['data' => $laborRate]);
    }

    public function destroyLaborRate(LaborRate $laborRate)
    {
        $laborRate->delete();
        return response()->json(['message' => 'Excluído']);
    }

    private function validateRate(Request $request): array
    {
        return $request->validate([
            'office_event_id' => 'required|exists:office_events,id',
            'city' => 'nullable|string|max:255',
            'km_from' => 'nullable|numeric|min:0',
            'km_to' => 'nullable|numeric|min:0',
            'daily_value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'category' => 'required|in:' . implode(',', array_keys(EventPlanningItem::CATEGORIES)),
            'description' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'collaborator_id' => 'nullable|exists:collaborators,id',
            'person_name' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:20',
            'role' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'unit_value' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    /**
     * Cria a conta a pagar. Usa os campos do evento em payables (event_id, category etc.)
     * quando a tabela já tem essas colunas (vêm com a tela do Financeiro).
     */
    private function createPayable(EventPlanningItem $item): ?Payable
    {
        $event = $item->event;
        $label = EventPlanningItem::CATEGORIES[$item->category] ?? 'Custo';
        $who = $item->person_name ?: ($item->supplier?->fantasy_name ?: $item->collaborator?->name);
        $values = [
            'description' => trim("{$label}: {$item->description}" . ($who ? " ({$who})" : '') . " - Evento {$event->id} {$event->name}"),
            'amount' => $item->total_value,
            'due_date' => ($item->due_date ?: ($event->date ?: now()))->format('Y-m-d'),
            'status' => 'pending',
        ];
        $optional = [
            'event_id' => $event->id,
            'supplier_id' => $item->supplier_id,
            'collaborator_id' => $item->collaborator_id,
            'category' => $item->category,
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $item->notes,
            'payableable_type' => EventPlanningItem::class,
            'payableable_id' => $item->id,
        ];
        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('payables', $column)) {
                $values[$column] = $value;
            }
        }

        // Sem as colunas novas do Financeiro, a tabela antiga exige operação financeira: não dá para lançar ainda.
        if (!Schema::hasColumn('payables', 'event_id')) {
            return null;
        }

        $payable = new Payable();
        $payable->forceFill($values)->save();
        return $payable;
    }

    /** Etapas cadastradas (Configurações > Planejamento) com as tarefas básicas. */
    private function stages()
    {
        return PlanningStage::with('tasks')->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Tarefas de um evento: as básicas de cada etapa (menos as tiradas deste evento) + as extras do evento.
     * $rows = event_planning_steps do evento, por step_key.
     */
    private function buildSteps($stages, $rows)
    {
        $list = collect();
        foreach ($stages as $stage) {
            $make = fn ($key, $label, $who, $custom, $row) => [
                'key' => $key,
                'stage_id' => $stage->id,
                'group' => $stage->name,
                'label' => $label,
                'who' => $who,
                'custom' => $custom,
                'done' => (bool) ($row?->done),
                'done_at' => $row?->done_at?->toIso8601String(),
                'done_by_name' => $row?->doneBy?->name,
                'note' => $row?->note,
            ];
            foreach ($stage->tasks as $task) {
                $row = $rows[$task->key] ?? null;
                if ($row?->removed) {
                    continue;
                }
                $list->push($make($task->key, $task->label, $task->who, false, $row));
            }
            $extras = $rows->filter(fn ($r) => $r->custom && !$r->removed && (int) $r->planning_stage_id === (int) $stage->id)->sortBy('id');
            foreach ($extras as $row) {
                $list->push($make($row->step_key, $row->label, $row->who, true, $row));
            }
        }
        return $list->values();
    }

    /** "+" na etapa: tarefa a mais só neste evento. */
    public function storeTask(Request $request, Event $event)
    {
        $data = $request->validate([
            'planning_stage_id' => 'required|exists:planning_stages,id',
            'label' => 'required|string|max:500',
            'who' => 'nullable|string|max:255',
        ]);
        EventPlanningStep::create([
            'event_id' => $event->id,
            'step_key' => 'extra_' . Str::lower(Str::random(12)),
            'planning_stage_id' => $data['planning_stage_id'],
            'label' => $data['label'],
            'who' => $data['who'] ?? null,
            'custom' => true,
        ]);
        return response()->json(['data' => $this->payload($event)], 201);
    }

    /** Excluir tarefa só deste evento: a extra é apagada; a básica some deste evento (continua no cadastro). */
    public function destroyTask(Event $event, string $stepKey)
    {
        $row = EventPlanningStep::where('event_id', $event->id)->where('step_key', $stepKey)->first();
        if ($row?->custom) {
            $row->delete();
        } else {
            abort_unless(PlanningTask::where('key', $stepKey)->exists(), 404, 'Tarefa não encontrada.');
            EventPlanningStep::updateOrCreate(
                ['event_id' => $event->id, 'step_key' => $stepKey],
                ['removed' => true, 'done' => false, 'done_by' => null, 'done_at' => null]
            );
        }
        return response()->json(['data' => $this->payload($event)]);
    }

    /** Volta para este evento as tarefas básicas que tinham sido tiradas. */
    public function restoreTasks(Event $event)
    {
        EventPlanningStep::where('event_id', $event->id)->where('custom', false)->where('removed', true)->update(['removed' => false]);
        return response()->json(['data' => $this->payload($event)]);
    }

    private function payload(Event $event): array
    {
        $saved = EventPlanningStep::with('doneBy:id,name')->where('event_id', $event->id)->get()->keyBy('step_key');
        $stages = $this->stages();
        $steps = $this->buildSteps($stages, $saved);

        $items = EventPlanningItem::with(['supplier:id,fantasy_name', 'collaborator:id,name', 'approvedBy:id,name'])
            ->where('event_id', $event->id)->orderBy('category')->orderBy('id')->get()
            ->map(fn (EventPlanningItem $i) => [
                'id' => $i->id,
                'category' => $i->category,
                'category_label' => EventPlanningItem::CATEGORIES[$i->category] ?? $i->category,
                'source' => $i->source,
                'office_event_id' => $i->office_event_id,
                'description' => $i->description,
                'supplier_id' => $i->supplier_id,
                'supplier_name' => $i->supplier?->fantasy_name,
                'collaborator_id' => $i->collaborator_id,
                'collaborator_name' => $i->collaborator?->name,
                'person_name' => $i->person_name,
                'cpf' => $i->cpf,
                'role' => $i->role,
                'quantity' => $i->quantity,
                'unit_value' => $i->unit_value,
                'total_value' => $i->total_value,
                'due_date' => $i->due_date?->format('Y-m-d'),
                'status' => $i->status,
                'approved_by_name' => $i->approvedBy?->name,
                'approved_at' => $i->approved_at?->toIso8601String(),
                'payable_id' => $i->payable_id,
                'notes' => $i->notes,
            ]);

        $planning = EventPlanning::where('event_id', $event->id)->first();
        $alignment = [];
        foreach (EventPlanning::ALIGNMENT_FIELDS as $key => $label) {
            $alignment[] = ['key' => $key, 'label' => $label, 'value' => $planning?->alignment[$key] ?? null];
        }
        $divergences = EventPlanningDivergence::with(['user:id,name', 'notifiedUser:id,name', 'resolvedBy:id,name'])
            ->where('event_id', $event->id)->orderByDesc('id')->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'description' => $d->description,
                'user_name' => $d->user?->name,
                'notified_name' => $d->notified_names ?: $d->notifiedUser?->name,
                'created_at' => $d->created_at?->toIso8601String(),
                'resolved_at' => $d->resolved_at?->toIso8601String(),
                'resolved_by_name' => $d->resolvedBy?->name,
            ]);

        $event->loadMissing('collaborator');
        $daysUntil = $event->date ? now()->startOfDay()->diffInDays(Carbon::parse($event->date)->startOfDay(), false) : null;
        $contracted = (float) $event->event_guests + (float) $event->parthenon_courtesies + (float) $event->partner_courtesies;
        $excess = $event->present_guests ? max(0, (int) round((float) $event->present_guests - $contracted)) : 0;

        return [
            'event_id' => $event->id,
            'info' => [
                'city' => $planning?->city,
                'distance_km' => $planning?->distance_km,
                'alignment' => $alignment,
                'table_map_file' => $planning?->tableMapFile ? [
                    'id' => $planning->tableMapFile->id,
                    // Nome sem o sufixo aleatório que o sistema acrescenta ao guardar.
                    'name' => preg_replace('/-[0-9a-f]{13}(\.\w+)$/', '$1', $planning->tableMapFile->name),
                    'extension' => $planning->tableMapFile->extension,
                    'url' => asset('storage' . $planning->tableMapFile->path),
                ] : null,
            ],
            'divergences' => $divergences,
            'open_divergences' => $divergences->whereNull('resolved_at')->count(),
            'alerts' => [
                'days_until' => $daysUntil,
                'addendum_by' => $daysUntil !== null && $daysUntil <= 30 ? 'planejamento' : 'consultor',
                'consultant_name' => $event->collaborator?->name,
                'materials_ok' => self::materialsFilled($event),
                'excess_guests' => $excess,
                'contracted_guests' => (int) $contracted,
                'present_guests' => $event->present_guests !== null ? (int) $event->present_guests : null,
            ],
            'steps' => $steps,
            'stages' => $stages->map(fn ($st) => ['id' => $st->id, 'name' => $st->name])->values(),
            'removed_count' => $saved->filter(fn ($r) => $r->removed && !$r->custom)->count(),
            'steps_done' => $steps->where('done', true)->count(),
            'steps_total' => $steps->count(),
            'items' => $items,
            'cost_planned' => (float) $items->where('status', 'previsto')->sum('total_value'),
            'cost_approved' => (float) $items->where('status', 'aprovado')->sum('total_value'),
            'payables_ready' => Schema::hasColumn('payables', 'event_id'),
        ];
    }

    /** A aba Materiais está preenchida: tem materiais e todos foram respondidos (Sim ou Não). */
    public static function materialsFilled(Event $event): bool
    {
        $rows = DB::table('event_material')->where('event_id', $event->id)->whereNull('deleted_at')->get(['selected']);
        return $rows->isNotEmpty() && $rows->every(fn ($r) => $r->selected !== null);
    }
}
