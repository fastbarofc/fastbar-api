<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventLaborConvocation;
use App\Models\EventLaborFinancialPlan;
use App\Models\EventLaborPayment;
use App\Models\EventLaborQuantification;
use App\Models\LaborSector;
use App\Models\LaborValueTable;
use App\Services\FileService;
use App\Services\PlanningRules;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Planejamento > Mão de Obra (André, 2026-11-04): Quantificação (planilha "Quantificação de Mão de Obra por
 * Evento": eventos do mês × setores, Qtd + Observações, Copa com Transporte), Convocação (quem vai em cada
 * setor) e Relatório Financeiro (valor por convocado, PDF para assinar). Só eventos em Planejamento/Concluído.
 */
class LaborConvocationController extends Controller
{
    private const STATUSES = ['Planejamento', 'Concluído'];

    // ---- Quantificação ----

    public function quantification(Request $request)
    {
        [$start, $end] = $this->monthRange($request->input('month'));
        $events = $this->monthEvents($start, $end);
        $sectors = $this->sectors($events->pluck('id'));

        $quant = EventLaborQuantification::whereIn('event_id', $events->pluck('id'))->get()->groupBy('event_id');
        $data = $events->map(function (Event $event) use ($quant, $sectors) {
            $rows = ($quant[$event->id] ?? collect())->keyBy('labor_sector_id');
            $cells = [];
            $total = 0;
            foreach ($sectors as $s) {
                $q = $rows[$s->id] ?? null;
                $cells[$s->id] = [
                    'quantity' => $q ? (float) $q->quantity : null,
                    'coordinator_quantity' => $q ? (int) $q->coordinator_quantity : null,
                    'transport_quantity' => $q && $s->has_transport ? (float) $q->transport_quantity : null,
                    'notes' => $q?->notes,
                    'excluded' => (bool) ($q?->excluded ?? false),
                    'truck_size' => $s->has_truck ? $q?->truck_size : null,
                    'truck_plate' => $s->has_truck ? $q?->truck_plate : null,
                ];
                $total += (float) ($q?->quantity ?? 0) + (int) ($q?->coordinator_quantity ?? 0);
            }
            return $this->eventInfo($event) + ['cells' => (object) $cells, 'total' => $total];
        });

        return response()->json(['data' => $data, 'sectors' => $sectors, 'month' => $start->format('Y-m')]);
    }

    /** Salva uma célula (setor do evento): quantidade, transporte e/ou observação. */
    public function saveQuantification(Request $request, Event $event)
    {
        $data = $request->validate([
            'labor_sector_id' => 'required|integer|exists:labor_sectors,id',
            'quantity' => 'nullable|numeric|min:0|max:9999',
            'coordinator_quantity' => 'nullable|integer|min:0|max:99',
            'transport_quantity' => 'nullable|numeric|min:0|max:9999',
            'notes' => 'nullable|string|max:255',
            'truck_size' => 'nullable|string|in:' . implode(',', self::TRUCK_SIZES),
            'truck_plate' => 'nullable|string|max:12',
        ]);
        abort_if($this->isLocked($event), 422, 'Evento concluído: a quantificação não pode mais ser alterada.');
        $owner = LaborSector::whereKey($data['labor_sector_id'])->value('event_id');
        abort_if($owner && (int) $owner !== (int) $event->id, 422, 'Este setor foi criado em outro evento.');
        abort_if(EventLaborQuantification::where('event_id', $event->id)->where('labor_sector_id', $data['labor_sector_id'])->where('excluded', true)->exists(), 422, 'Este setor foi tirado do evento. Use ± Setores para colocar de volta.');

        $row = EventLaborQuantification::firstOrNew(['event_id' => $event->id, 'labor_sector_id' => $data['labor_sector_id']]);
        foreach (['truck_size', 'truck_plate'] as $k) {
            if (array_key_exists($k, $data)) {
                $row->{$k} = $data[$k] !== null && trim($data[$k]) !== '' ? mb_strtoupper(trim($data[$k])) : null;
            }
        }
        foreach (['quantity', 'coordinator_quantity', 'transport_quantity', 'notes'] as $k) {
            if (array_key_exists($k, $data)) {
                $row->{$k} = $k === 'notes' ? ($data[$k] !== null && trim($data[$k]) !== '' ? trim($data[$k]) : null)
                    : ($k === 'coordinator_quantity' ? (int) ($data[$k] ?? 0) : (float) ($data[$k] ?? 0));
            }
        }
        $row->updated_by = $request->user()?->id;
        $truckChanged = $row->isDirty('truck_size');
        $row->save();
        if ($truckChanged) {
            // Carga: a diária dos convocados ainda não pagos acompanha o caminhão escolhido.
            $event->loadMissing(['eventPlace.address.city']);
            $value = $row->truck_size ? LaborValueTable::current()?->valueFor($row->truck_size, $event->eventPlace?->address?->city?->name, null, $event->duration !== null ? (float) $event->duration : null) : null;
            EventLaborPayment::where('event_id', $event->id)->where('labor_sector_id', $row->labor_sector_id)->whereNull('paid_at')
                ->update(['base_value' => (float) ($value ?? 0)]);
        }

        return response()->json(['data' => [
            'quantity' => (float) $row->quantity,
            'coordinator_quantity' => (int) $row->coordinator_quantity,
            'transport_quantity' => (float) $row->transport_quantity,
            'notes' => $row->notes,
            'truck_size' => $row->truck_size,
            'truck_plate' => $row->truck_plate,
            'total' => (float) EventLaborQuantification::where('event_id', $event->id)->sum('quantity')
                + (int) EventLaborQuantification::where('event_id', $event->id)->sum('coordinator_quantity'),
        ]]);
    }

    /** Base dos setores (Configurações > Planejamento > Setores de mão de obra) + linhas da Tabela de valores. */
    public function sectorsIndex()
    {
        $table = LaborValueTable::current();
        $roles = $table ? array_values(array_unique(array_merge($table->rowNames('VALOR BASE'), $table->rowNames('EXTRAS')))) : [];
        $sectors = LaborSector::whereNull('event_id')->where('active', true)->orderBy('position')->orderBy('id')->get();
        return response()->json(['data' => $sectors, 'roles' => $roles]);
    }

    /** Tamanhos de caminhão do setor Carga = linhas da seção CARGAS da Tabela de valores. */
    public const TRUCK_SIZES = ['3/4', 'TOCO', 'TRUCADO'];

    /** Linha da Tabela de valores que paga o setor: no Carga, o caminhão escolhido no evento. */
    public static function sectorRole(?LaborSector $sector, $quantification = null): ?string
    {
        if ($sector?->has_truck) {
            return $quantification?->truck_size ?: null;
        }
        return $sector?->value_table_role ?: null;
    }

    private const SECTOR_COLORS = ['#8E6B23', '#5B6C8F', '#7A4E7E', '#3F7D6E', '#9C5B3B', '#4F6D3A', '#6B5B95', '#A0522D'];

    private function sectorRules(?LaborSector $sector = null): array
    {
        $req = $sector ? 'sometimes|required' : 'required';
        return [
            'name' => $req . '|string|max:80',
            'value_table_role' => 'sometimes|nullable|string|max:80',
            'has_transport' => 'sometimes|boolean',
            'has_coordination' => 'sometimes|boolean',
            'has_truck' => 'sometimes|boolean',
            'color' => 'sometimes|nullable|string|max:9',
        ];
    }

    private function nameTaken(string $name, ?int $eventId, ?int $ignoreId = null): bool
    {
        return LaborSector::where('active', true)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->where(fn ($q) => $q->whereNull('event_id')->when($eventId, fn ($q2) => $q2->orWhere('event_id', $eventId)))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
    }

    private function newSector(array $data, ?int $eventId): LaborSector
    {
        abort_if($this->nameTaken($data['name'], $eventId), 422, 'Já existe um setor com esse nome.');
        $count = LaborSector::count();
        return LaborSector::create([
            'event_id' => $eventId,
            'name' => trim($data['name']),
            'value_table_role' => $data['value_table_role'] ?? 'GARÇOM',
            'has_transport' => (bool) ($data['has_transport'] ?? false),
            'color' => $data['color'] ?? self::SECTOR_COLORS[$count % count(self::SECTOR_COLORS)],
            'position' => (int) LaborSector::whereNull('event_id')->max('position') + 1,
            'active' => true,
        ]);
    }

    /** Novo setor na base (vale para todos os eventos). */
    public function storeSector(Request $request)
    {
        return response()->json(['data' => $this->newSector($request->validate($this->sectorRules()), null)], 201);
    }

    /** Editar setor da base: nome, cor, diária pela linha da Tabela de valores, transporte. */
    public function updateSector(Request $request, LaborSector $sector)
    {
        $data = $request->validate($this->sectorRules($sector));
        if (isset($data['name'])) {
            abort_if($this->nameTaken($data['name'], $sector->event_id, $sector->id), 422, 'Já existe um setor com esse nome.');
            $data['name'] = trim($data['name']);
        }
        $sector->update($data);
        return response()->json(['data' => $sector->fresh()]);
    }

    /** Ordem das colunas: lista de ids na ordem nova. */
    public function reorderSectors(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach (array_values($ids) as $i => $id) {
            LaborSector::whereKey($id)->whereNull('event_id')->update(['position' => $i + 1]);
        }
        return response()->json(['data' => ['ok' => true]]);
    }

    private function sectorUsed(LaborSector $sector): bool
    {
        return EventLaborQuantification::where('labor_sector_id', $sector->id)
                ->where(fn ($q) => $q->where('quantity', '>', 0)->orWhere('coordinator_quantity', '>', 0)->orWhereNotNull('notes'))->exists()
            || EventLaborConvocation::where('labor_sector_id', $sector->id)->exists()
            || EventLaborPayment::where('labor_sector_id', $sector->id)->exists();
    }

    /**
     * Excluir setor da base. Se algum evento já usou, ele só sai da base (active = false) e
     * continua no relatório dos eventos que usaram.
     */
    public function destroySector(LaborSector $sector)
    {
        $used = $this->sectorUsed($sector);
        if ($used) {
            $sector->update(['active' => false]);
        } else {
            EventLaborQuantification::where('labor_sector_id', $sector->id)->delete();
            $sector->delete();
        }
        return response()->json(['deleted' => true, 'kept_history' => $used]);
    }

    /**
     * "Colocar setor" no botão ± Setores da Quantificação: volta um setor que tinha sido tirado do evento.
     * A quantidade é digitada depois, na própria Quantificação.
     */
    public function addSectorToEvent(Request $request, Event $event)
    {
        $data = $request->validate(['labor_sector_id' => 'required|integer|exists:labor_sectors,id'], ['labor_sector_id.required' => 'Escolha o setor.']);
        abort_if($this->isLocked($event), 422, 'Evento concluído: a mão de obra não pode mais ser alterada.');
        $sector = LaborSector::findOrFail($data['labor_sector_id']);
        abort_if($sector->event_id && (int) $sector->event_id !== (int) $event->id, 422, 'Este setor foi criado em outro evento.');
        // Volta em branco: a linha marcada como tirada é apagada e a quantidade é digitada na tabela.
        EventLaborQuantification::where('event_id', $event->id)->where('labor_sector_id', $sector->id)->where('excluded', true)->delete();

        return response()->json(['data' => $sector], 201);
    }


    // ---- Planejamento Financeiro ----

    /**
     * Custo previsto da mão de obra: por evento × setor, quantidade da Quantificação × diária da Tabela de valores
     * (linha do setor, coluna pela cidade do local e duração do evento). Compara com o realizado
     * (Relatório Financeiro). Setor sem linha na Tabela de valores aparece "sem valor".
     */
    public function financialPlan(Request $request)
    {
        [$start, $end] = $this->monthRange($request->input('month'));
        $events = $this->monthEvents($start, $end);
        $sectors = $this->sectors($events->pluck('id'));
        $roles = LaborSector::whereIn('id', $sectors->pluck('id'))->pluck('value_table_role', 'id');
        $ids = $events->pluck('id');
        $table = LaborValueTable::current();

        $quant = EventLaborQuantification::whereIn('event_id', $ids)->get()->groupBy('event_id');
        $paid = EventLaborPayment::whereIn('event_id', $ids)->get()->groupBy('event_id');

        $data = $events->map(function (Event $event) use ($quant, $paid, $sectors, $roles, $table) {
            $q = ($quant[$event->id] ?? collect())->keyBy('labor_sector_id');
            $city = $event->eventPlace?->address?->city?->name;
            $hours = $event->duration !== null ? (float) $event->duration : null;
            $cells = [];
            $people = 0;
            $planned = 0;
            $missing = [];
            foreach ($sectors as $s) {
                $qty = (float) ($q[$s->id]->quantity ?? 0) + (int) ($q[$s->id]->coordinator_quantity ?? 0);
                if ($qty <= 0) {
                    $cells[$s->id] = null;
                    continue;
                }
                $role = self::sectorRole($s, $q[$s->id] ?? null);
                $unit = $role ? $table?->valueFor($role, $city, null, $hours) : null;
                if ($unit === null) {
                    $missing[] = $s->name;
                }
                $total = round($qty * (float) ($unit ?? 0), 2);
                $cells[$s->id] = ['quantity' => $qty, 'unit_value' => $unit, 'total' => $total];
                $people += $qty;
                $planned += $total;
            }
            $actual = ($paid[$event->id] ?? collect())->sum(fn (EventLaborPayment $p) => $p->total());

            return $this->eventInfo($event) + [
                'cells' => (object) $cells,
                'people' => $people,
                'planned' => round($planned, 2),
                'actual' => round($actual, 2),
                'missing' => $missing,
            ];
        });

        return response()->json([
            'data' => $data,
            'sectors' => $sectors,
            'month' => $start->format('Y-m'),
            'value_table_date' => $table?->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Planejamento Financeiro de um evento no formato da planilha do André:
     * uma linha por setor (separada pela cidade dos convocados quando vêm de cidades diferentes),
     * valores do Relatório Financeiro; quem falta convocar entra pela diária da Tabela de valores.
     * Mais as linhas extras digitadas, Receita (valor do evento), KPI Freelancer = mão de obra / receita.
     */
    public function eventFinancialPlan(Event $event)
    {
        $this->syncPaymentRows($event);
        return response()->json(['data' => $this->eventPlanPayload($event)]);
    }

    public function saveEventFinancialPlan(Request $request, Event $event)
    {
        $data = $request->validate([
            'extra_lines' => 'nullable|array',
            'extra_lines.*.description' => 'nullable|string|max:120',
            'extra_lines.*.quantity' => 'nullable|numeric|min:0',
            'extra_lines.*.unit_value' => 'nullable|numeric|min:0',
            'extra_lines.*.highlight' => 'nullable|boolean',
            'costs_confirmed' => 'nullable|boolean',
        ]);
        if ($this->isLocked($event)) {
            return response()->json(['message' => 'Evento Concluído: o planejamento financeiro fica só para consulta.'], 422);
        }
        $plan = EventLaborFinancialPlan::firstOrNew(['event_id' => $event->id]);
        if ($request->has('extra_lines')) {
            $plan->extra_lines = collect($data['extra_lines'] ?? [])->map(fn ($l) => [
                'description' => trim((string) ($l['description'] ?? '')),
                'quantity' => (float) ($l['quantity'] ?? 0),
                'unit_value' => (float) ($l['unit_value'] ?? 0),
                'highlight' => (bool) ($l['highlight'] ?? false),
            ])->values()->all();
        }
        if ($request->has('costs_confirmed')) {
            $plan->costs_confirmed = $data['costs_confirmed'];
        }
        $plan->updated_by = $request->user()?->id;
        $plan->save();
        $this->syncPaymentRows($event);

        return response()->json(['data' => $this->eventPlanPayload($event)]);
    }

    /** PDF do Planejamento Financeiro: um evento por folha (o selecionado ou os eventos da semana). ?file=1 devolve o arquivo para imprimir. */
    public function financialPlanPdf(Request $request)
    {
        $data = $request->validate(['event_ids' => 'required|array|min:1', 'event_ids.*' => 'integer|exists:events,id']);
        $events = Event::whereIn('id', $data['event_ids'])->orderBy('date')->orderBy('time')->get();
        $plans = $events->map(function (Event $event) {
            $this->syncPaymentRows($event);
            return $this->eventPlanPayload($event);
        });
        $viewContent = view('pdfs.labor-financial-plan-pdf', ['plans' => $plans, 'date' => Carbon::now()->format('d/m/Y')]);
        try {
            $name = 'planejamento-financeiro-' . $events->pluck('id')->implode('-') . '-' . time();
            $name = mb_substr($name, 0, 120);
            $pdfUrl = FileService::generatePdf($viewContent, $name, null, null, [
                'page-size' => 'A4', 'margin-top' => '10mm', 'margin-bottom' => '10mm', 'margin-left' => '10mm', 'margin-right' => '10mm',
                'footer-font-size' => 8, 'footer-right' => '[page] de [topage]', 'footer-spacing' => 3,
            ]);
            if ($request->boolean('file')) {
                return response()->file(storage_path('app/public/pdfs/' . $name . '.pdf'), ['Content-Type' => 'application/pdf']);
            }
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    private function eventPlanPayload(Event $event): array
    {
        $event->loadMissing(['client:id,name', 'eventPlace:id,name', 'eventPlace.address.city:id,name', 'eventStatus:id,name', 'collaborator:id,name']);
        $city = $event->eventPlace?->address?->city?->name;
        $hours = $event->duration !== null ? (float) $event->duration : null;
        $table = LaborValueTable::current();
        $home = 'Viçosa';
        $norm = fn ($v) => mb_strtolower(trim((string) $v));

        $quant = EventLaborQuantification::where('event_id', $event->id)->get()->keyBy('labor_sector_id');
        $payments = EventLaborPayment::with('collaborator.address.city:id,name')->where('event_id', $event->id)->get()->groupBy('labor_sector_id');

        $lines = [];
        foreach (LaborSector::orderBy('position')->get() as $sector) {
            $people = $payments->get($sector->id, collect());
            $needed = (int) ceil((float) ($quant->get($sector->id)?->quantity ?? 0)) + (int) ($quant->get($sector->id)?->coordinator_quantity ?? 0);
            if ($needed <= 0 && $people->isEmpty()) {
                continue;
            }
            $groups = $people->groupBy(fn (EventLaborPayment $p) => $p->collaborator?->address?->city?->name ?: '');
            foreach ($groups->sortKeys() as $groupCity => $rows) {
                $total = round($rows->sum(fn (EventLaborPayment $p) => $p->total()), 2);
                $qty = $rows->count();
                $lines[] = [
                    'sector_id' => $sector->id,
                    'description' => $sector->name . ($groupCity !== '' && ($groups->count() > 1 || $norm($groupCity) !== $norm($home)) ? ' ' . $groupCity : ''),
                    'color' => $sector->color,
                    'quantity' => $qty,
                    'unit_value' => $qty ? round($total / $qty, 2) : 0,
                    'total' => $total,
                    'highlight' => $groupCity !== '' && $norm($groupCity) !== $norm($home),
                    'source' => 'convocacao',
                ];
            }
            $missing = $needed - $people->count();
            if ($missing > 0) {
                $role = self::sectorRole($sector, $quant->get($sector->id));
                $unit = $role ? $table?->valueFor($role, $city, null, $hours) : null;
                $lines[] = [
                    'sector_id' => $sector->id,
                    'description' => $sector->name . ($people->isNotEmpty() ? ' (a convocar)' : ''),
                    'color' => $sector->color,
                    'quantity' => $missing,
                    'unit_value' => $unit,
                    'total' => round($missing * (float) ($unit ?? 0), 2),
                    'highlight' => false,
                    'source' => 'quantificacao',
                ];
            }
        }

        $plan = EventLaborFinancialPlan::where('event_id', $event->id)->first();
        $extra = collect($plan?->extra_lines ?? [])->map(fn ($l) => $l + ['total' => round((float) $l['quantity'] * (float) $l['unit_value'], 2)])->values();

        $totalQty = collect($lines)->sum('quantity') + $extra->sum('quantity');
        $totalValue = round(collect($lines)->sum('total') + $extra->sum('total'), 2);
        // Receita: valor total do evento; sem ele, convidados × valor unitário.
        $revenue = $event->total_value !== null && (float) $event->total_value > 0
            ? (float) $event->total_value
            : (($event->event_guests && $event->guest_unit_value) ? round((float) $event->event_guests * (float) $event->guest_unit_value, 2) : null);

        return [
            'event' => $this->eventInfo($event) + ['total_value' => $revenue],
            'standard_value' => $table?->valueFor('GARÇOM', $city, null, $hours),
            'lines' => $lines,
            'extra_lines' => $extra,
            'costs_confirmed' => $plan?->costs_confirmed,
            'total_quantity' => $totalQty,
            'total_value' => $totalValue,
            'revenue' => $revenue,
            'kpi' => $revenue ? round($totalValue / $revenue * 100, 2) : null,
        ];
    }

    // ---- Convocação ----

    /** Eventos do mês com os setores (quantidade necessária) e os convocados de cada um. */
    public function index(Request $request)
    {
        [$start, $end] = $this->monthRange($request->input('month'));
        $events = $this->monthEvents($start, $end);
        $ids = $events->pluck('id');
        $sectors = $this->sectors($ids);

        $quant = EventLaborQuantification::whereIn('event_id', $ids)->get()->groupBy('event_id');
        $conv = EventLaborConvocation::with('collaborator:id,name,nickname,phone_1')->whereIn('event_id', $ids)->get()->groupBy('event_id');

        $data = $events->map(function (Event $event) use ($quant, $conv, $sectors) {
            $q = ($quant[$event->id] ?? collect())->keyBy('labor_sector_id');
            $c = ($conv[$event->id] ?? collect())->groupBy('labor_sector_id');
            $functions = [];
            foreach ($sectors as $s) {
                $needed = (float) ($q[$s->id]->quantity ?? 0);
                $coordNeeded = (int) ($q[$s->id]->coordinator_quantity ?? 0);
                $people = ($c[$s->id] ?? collect())->sortByDesc('is_coordinator')->map(fn ($x) => [
                    'id' => $x->collaborator?->id, 'name' => $x->collaborator?->name, 'nickname' => $x->collaborator?->nickname, 'phone' => $x->collaborator?->phone_1,
                    'is_coordinator' => (bool) $x->is_coordinator,
                ])->filter(fn ($p) => $p['id'])->values();
                if ($needed <= 0 && $coordNeeded <= 0 && $people->isEmpty()) {
                    continue;
                }
                $functions[] = [
                    'sector_id' => $s->id,
                    'description' => $s->name,
                    'color' => $s->color,
                    'needed' => $needed,
                    'coordinators_needed' => $coordNeeded,
                    'notes' => $q[$s->id]->notes ?? null,
                    'collaborators' => $people,
                ];
            }
            return $this->eventInfo($event) + [
                'functions' => $functions,
                'needed' => array_sum(array_column($functions, 'needed')) + array_sum(array_column($functions, 'coordinators_needed')),
                'assigned' => array_sum(array_map(fn ($f) => count($f['collaborators']), $functions)),
            ];
        });

        return response()->json(['data' => $data, 'month' => $start->format('Y-m'), 'one_sector_rule' => PlanningRules::enabled('convocacao_um_setor')]);
    }

    /** Busca colaboradores ativos para convocar (nome, apelido ou ID). */
    public function collaborators(Request $request)
    {
        $q = trim((string) $request->input('search', ''));
        $rows = Collaborator::query()
            ->with('sectorOffice:id,name')
            ->where('active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")->orWhere('nickname', 'like', "%{$q}%");
                    if (ctype_digit($q)) {
                        $w->orWhere('id', (int) $q);
                    }
                });
            })
            ->orderBy('name')->limit(30)->get(['id', 'name', 'nickname', 'phone_1', 'sector_office_id']);

        return response()->json(['data' => $rows->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->name, 'nickname' => $c->nickname, 'phone' => $c->phone_1, 'office' => $c->sectorOffice?->name,
        ])]);
    }

    public function attach(Request $request, Event $event)
    {
        $data = $request->validate([
            'labor_sector_id' => 'required|integer|exists:labor_sectors,id',
            'collaborator_id' => 'required|integer|exists:collaborators,id',
            'is_coordinator' => 'nullable|boolean',
        ]);
        abort_if($this->isLocked($event), 422, 'Evento concluído: a convocação não pode mais ser alterada.');

        if (PlanningRules::enabled('convocacao_um_setor')) {
            $other = EventLaborConvocation::with('sector:id,name')->where('event_id', $event->id)
                ->where('collaborator_id', $data['collaborator_id'])->where('labor_sector_id', '!=', $data['labor_sector_id'])->first();
            if ($other) {
                return response()->json(['message' => 'Essa pessoa já está convocada em ' . ($other->sector?->name ?? 'outro setor') . ' neste evento.'], 422);
            }
        }

        $conv = EventLaborConvocation::firstOrCreate(
            ['event_id' => $event->id, 'labor_sector_id' => $data['labor_sector_id'], 'collaborator_id' => $data['collaborator_id']],
            ['created_by' => $request->user()?->id]
        );
        if (array_key_exists('is_coordinator', $data) && (bool) $data['is_coordinator'] !== (bool) $conv->is_coordinator) {
            $conv->update(['is_coordinator' => (bool) $data['is_coordinator']]);
        }

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function detach(Request $request, Event $event, LaborSector $sector, Collaborator $collaborator)
    {
        abort_if($this->isLocked($event), 422, 'Evento concluído: a convocação não pode mais ser alterada.');
        EventLaborConvocation::where('event_id', $event->id)->where('labor_sector_id', $sector->id)->where('collaborator_id', $collaborator->id)->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    /**
     * Tirar um setor (qualquer um, fixo ou criado) só deste evento: apaga a quantidade/observação da
     * Quantificação, os convocados do setor e as linhas ainda não pagas do Relatório Financeiro.
     * Os outros eventos não mudam. Se alguém do setor já está como Pago, não deixa tirar.
     */
    public function removeSectorFromEvent(Event $event, LaborSector $sector)
    {
        abort_if($this->isLocked($event), 422, 'Evento concluído: a mão de obra não pode mais ser alterada.');
        $paid = EventLaborPayment::where('event_id', $event->id)->where('labor_sector_id', $sector->id)->whereNotNull('paid_at')->count();
        abort_if($paid > 0, 422, "Não dá para tirar: {$paid} pessoa(s) de {$sector->name} já estão como Pago no Relatório Financeiro.");

        EventLaborQuantification::where('event_id', $event->id)->where('labor_sector_id', $sector->id)->delete();
        EventLaborConvocation::where('event_id', $event->id)->where('labor_sector_id', $sector->id)->delete();
        EventLaborPayment::where('event_id', $event->id)->where('labor_sector_id', $sector->id)->whereNull('paid_at')->delete();
        if (!$sector->event_id && !$sector->occasional) {
            // Setor da base: fica marcado como tirado deste evento (as células somem só aqui).
            EventLaborQuantification::create(['event_id' => $event->id, 'labor_sector_id' => $sector->id, 'excluded' => true, 'updated_by' => auth()->id()]);
        }
        // Setor criado só neste evento some junto. Setor do primeiro "+ Setor" (occasional, sem evento)
        // também some quando nenhum outro evento usa.
        if ((int) $sector->event_id === (int) $event->id || ($sector->occasional && !$sector->event_id && !$this->sectorUsed($sector))) {
            EventLaborQuantification::where('labor_sector_id', $sector->id)->delete();
            $sector->delete();
        }

        return response()->json(['data' => ['ok' => true]]);
    }

    // ---- Relatório Financeiro ----

    public function financialReport(Request $request, Event $event)
    {
        $this->syncPaymentRows($event);

        return response()->json(['data' => $this->reportPayload($event)]);
    }

    public function savePayment(Request $request, Event $event, EventLaborPayment $payment)
    {
        abort_if($payment->event_id !== $event->id, 404);
        $data = $request->validate([
            'base_value' => 'nullable|numeric|min:0',
            'extra_hours_value' => 'nullable|numeric|min:0',
            'after_party_value' => 'nullable|numeric|min:0',
            'coordination_value' => 'nullable|numeric|min:0',
            'other_value' => 'nullable|numeric|min:0',
            'clt_value' => 'nullable|numeric|min:0',
            'coordination_role' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:255',
            'paid' => 'nullable|boolean',
        ]);
        $update = [];
        if (array_key_exists('coordination_role', $data)) {
            // Escolher a coordenação já traz o adicional da Tabela de valores para a cidade do evento.
            $role = $data['coordination_role'] ?: null;
            $update['coordination_role'] = $role;
            $update['coordination_value'] = $role ? (float) ($this->coordinationValue($event, $role) ?? 0) : 0;
            unset($data['coordination_value']);
        }
        foreach (['base_value', 'extra_hours_value', 'after_party_value', 'coordination_value', 'clt_value', 'other_value'] as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = (float) ($data[$k] ?? 0);
            }
        }
        if (array_key_exists('notes', $data)) {
            $update['notes'] = $data['notes'];
        }
        if (array_key_exists('paid', $data)) {
            $update['paid_at'] = $data['paid'] ? ($payment->paid_at ?? now()) : null;
        }
        $update['updated_by'] = $request->user()?->id;
        $payment->update($update);

        return response()->json(['data' => $this->reportPayload($event)]);
    }

    /** Recalcula as diárias pela Tabela de valores (sem mexer nos extras nem em quem já foi pago). */
    public function recalculate(Request $request, Event $event)
    {
        $this->syncPaymentRows($event, true);

        return response()->json(['data' => $this->reportPayload($event)]);
    }

    public function pdf(Event $event)
    {
        $this->syncPaymentRows($event);
        $event->load(['client', 'eventPlace']);
        $report = $this->reportPayload($event);
        $date = Carbon::now()->format('d/m/Y');

        $sections = $this->reportSections($event, $report);

        // Capa: coordenadores (mesma lista do modelo em papel); preenche com quem tem a coordenação marcada no relatório.
        $byRole = function (array $roles) use ($report) {
            return collect($report['rows'])->first(fn ($r) => in_array(mb_strtoupper((string) ($r['coordination_role'] ?? '')), $roles, true));
        };
        // Com coordenadores na Quantificação, a capa lista um por linha, setor a setor (André 2026-11-06).
        $coordinators = [];
        foreach ($sections as $sec) {
            $label = match (mb_strtolower($sec['name'])) {
                'coordenação geral' => 'Coordenador geral',
                'fiscal' => 'Fiscal de evento',
                default => 'Coordenador - ' . $sec['name'],
            };
            for ($i = 0; $i < ($sec['coord_slots'] ?? 0); $i++) {
                $coordinators[] = ['label' => $label, 'person' => $sec['rows'][$i] ?? null];
            }
        }
        if ($coordinators) {
            goto capa_pronta;
        }
        $coordinators = [
            ['label' => 'Coordenador geral', 'person' => $byRole(['COORDENADOR GERAL'])],
            ['label' => 'Maitre', 'person' => null],
            ['label' => 'Coordenador de mesa temática', 'person' => $byRole(['MESA'])],
            ['label' => 'Chef de cozinha', 'person' => $byRole(['CHEFS DE COZINHA (EVENTO)', 'COZINHA'])],
            ['label' => 'Chefe de copa', 'person' => $byRole(['COPA'])],
            ['label' => 'Coordenador de bar quente', 'person' => null],
            ['label' => 'Coordenador da fábrica', 'person' => null],
            ['label' => 'Fiscal de caminhão', 'person' => $byRole(['FISCAL DE CAMINHÃO'])],
            ['label' => 'Fiscal de evento', 'person' => $byRole(['FISCAL DE EVENTO'])],
            ['label' => 'Coordenador de portaria/segurança', 'person' => $byRole(['PORTARIA'])],
        ];
        capa_pronta:

        $viewContent = view('pdfs.labor-financial-report-pdf', [
            'event' => $event, 'report' => $report, 'date' => $date,
            'sections' => $sections, 'coordinators' => $coordinators,
        ]);
        $eventDate = $event->date ? Carbon::parse($event->date)->format('d/m/Y') : '';
        try {
            // Cabeçalho com os dados do evento e "Página X de Y" em todas as folhas.
            // Valor base = diária de garçom da Tabela de valores na cidade do local do evento.
            $event->loadMissing(['eventPlace.address.city']);
            $city = $event->eventPlace?->address?->city?->name;
            $baseValue = LaborValueTable::current()?->valueFor('GARÇOM', $city, null, $event->duration !== null ? (float) $event->duration : null);
            $header = view('pdfs.labor-financial-report-header', ['event' => $event, 'date' => $date, 'city' => $city, 'baseValue' => $baseValue])->render();
            $pdfName = 'relatorio-financeiro-mao-de-obra-' . $event->id . '-' . time();
            $pdfUrl = FileService::generatePdf($viewContent, $pdfName, null, $header, [
                'orientation' => 'Landscape',
                'header-spacing' => 3,
                'margin-top' => '47mm', 'margin-bottom' => '12mm', 'margin-left' => '8mm', 'margin-right' => '8mm',
                'footer-font-size' => 8,
                'footer-left' => 'Evento #' . $event->id . ' - ' . $event->name . ($eventDate ? ' - ' . $eventDate : ''),
                'footer-right' => '[page] de [topage]',
                'footer-spacing' => 3,
            ]);
            if (request()->boolean('file')) {
                return response()->file(storage_path('app/public/pdfs/' . $pdfName . '.pdf'), ['Content-Type' => 'application/pdf']);
            }
            return response()->json(['pdf_url' => $pdfUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    /** Diária: linha da Tabela de valores do setor (value_table_role) ou da função do colaborador. */
    public function syncPaymentRows(Event $event, bool $recalculate = false): void
    {
        $event->loadMissing(['eventPlace.address.city']);
        $convocations = EventLaborConvocation::with('collaborator.sectorOffice:id,name')->where('event_id', $event->id)->get();
        $sectors = LaborSector::all()->keyBy('id');
        $table = LaborValueTable::current();
        $city = $event->eventPlace?->address?->city?->name;
        $hours = $event->duration !== null ? (float) $event->duration : null;

        $quants = EventLaborQuantification::where('event_id', $event->id)->get()->keyBy('labor_sector_id');
        $cltApplies = self::cltBonusApplies($event);
        $keep = [];
        foreach ($convocations as $c) {
            $sector = $sectors[$c->labor_sector_id] ?? null;
            $base = null;
            $roles = $sector?->has_truck ? [self::sectorRole($sector, $quants->get($c->labor_sector_id))]
                : [$sector?->value_table_role, $c->collaborator?->sectorOffice?->name, $sector?->name];
            foreach (array_filter($roles) as $role) {
                $base = $table?->valueFor($role, $city, null, $hours);
                if ($base !== null) {
                    break;
                }
            }
            $row = EventLaborPayment::firstOrNew([
                'event_id' => $event->id,
                'collaborator_id' => $c->collaborator_id,
                'labor_sector_id' => $c->labor_sector_id,
            ]);
            $fresh = !$row->exists || ($recalculate && !$row->paid_at);
            if ($fresh) {
                $row->base_value = (float) ($base ?? 0);
            }
            if (!$row->exists && !$row->coordination_role) {
                $row->coordination_role = $c->is_coordinator
                    ? ($this->sectorCoordinationRole($sector?->name, $table) ?? $this->defaultCoordinationRole($sector?->name, $table))
                    : $this->defaultCoordinationRole($sector?->name, $table);
            }
            if ($fresh) {
                $row->clt_value = $cltApplies && $c->collaborator?->employment_type === 'clt' ? self::CLT_BONUS : 0;
            }
            if ($fresh && $row->coordination_role) {
                $row->coordination_value = (float) ($table?->valueFor($row->coordination_role, $city, null, $hours, self::COORD_SECTION) ?? 0);
            }
            $row->save();
            $keep[] = $row->id;
        }
        EventLaborPayment::where('event_id', $event->id)->whereNotIn('id', $keep)->whereNull('paid_at')->delete();
    }

    /**
     * Blocos do Relatório Financeiro: um por setor com Qtd > 0 na Quantificação (ou com alguém convocado).
     * Linhas = Qtd + linhas a mais ("+ Linha"); os convocados preenchem e o resto fica em branco.
     */
    private function reportSections(Event $event, array $report)
    {
        $quantities = EventLaborQuantification::where('event_id', $event->id)->get()->keyBy('labor_sector_id');
        $bySector = collect($report['rows'])->groupBy('labor_sector_id');
        return LaborSector::orderBy('position')->get()
            ->map(function (LaborSector $sector) use ($quantities, $bySector) {
                $people = $bySector->get($sector->id, collect())->values();
                $q = $quantities->get($sector->id);
                $qty = (int) ceil((float) ($q?->quantity ?? 0));
                $coordQty = (int) ($q?->coordinator_quantity ?? 0);
                $extra = (int) ($q?->extra_lines ?? 0);
                // Coordenadores primeiro (linhas = Coord. da Quantificação), depois os demais.
                $coords = $people->filter(fn ($p) => !empty($p['is_coordinator']))->values();
                $others = $people->reject(fn ($p) => !empty($p['is_coordinator']))->values();
                $coordSlots = max($coordQty, $coords->count());
                $rows = $coords->all();
                for ($i = $coords->count(); $i < $coordSlots; $i++) {
                    $rows[] = null;
                }
                $rows = array_merge($rows, $others->all());
                return [
                    'id' => $sector->id,
                    'name' => $sector->name,
                    'color' => $sector->color ?: '#2f2b26',
                    'has_coordination' => (bool) $sector->has_coordination,
                    'has_truck' => (bool) $sector->has_truck,
                    'truck_size' => $sector->has_truck ? $q?->truck_size : null,
                    'truck_plate' => $sector->has_truck ? $q?->truck_plate : null,
                    'quantity' => $qty,
                    'coordinator_quantity' => $coordQty,
                    'coord_slots' => $coordSlots,
                    'extra_lines' => $extra,
                    'rows' => $rows,
                    'lines' => max($coordSlots + $qty + $extra, count($rows)),
                    'total' => round($people->sum('total'), 2),
                ];
            })
            ->filter(fn ($s) => $s['lines'] > 0)->values();
    }

    /** "+ Linha" / "− Linha" no fim de um setor do Relatório Financeiro. */
    public function reportLines(Request $request, Event $event)
    {
        $data = $request->validate(['labor_sector_id' => 'required|exists:labor_sectors,id', 'delta' => 'required|integer|in:-1,1']);
        if ($this->isLocked($event)) {
            return response()->json(['message' => 'Evento Concluído: o relatório fica só para consulta.'], 422);
        }
        $q = EventLaborQuantification::firstOrNew(['event_id' => $event->id, 'labor_sector_id' => $data['labor_sector_id']]);
        if (!$q->exists) {
            $q->quantity = 0;
            $q->transport_quantity = 0;
        }
        $q->extra_lines = max(0, (int) $q->extra_lines + (int) $data['delta']);
        $q->updated_by = $request->user()?->id;
        $q->save();

        $this->syncPaymentRows($event);
        return response()->json(['data' => $this->reportPayload($event)]);
    }

    private function reportPayload(Event $event): array
    {
        $coordKeys = EventLaborConvocation::where('event_id', $event->id)->where('is_coordinator', true)->get()
            ->map(fn ($c) => $c->collaborator_id . '-' . $c->labor_sector_id)->flip();
        // Presença confirmada pelo celular (rosto ou assinatura com o dedo) entra no lugar da assinatura em papel.
        $attendance = \App\Models\EventLaborAttendance::where('event_id', $event->id)->get()->keyBy('collaborator_id');
        $rows = EventLaborPayment::with(['collaborator', 'sector:id,name,position'])
            ->where('event_id', $event->id)->get()
            ->sortBy(fn ($p) => [$p->sector?->position ?? 99, $p->collaborator?->name ?? ''])->values()
            ->map(fn (EventLaborPayment $p) => [
                'id' => $p->id,
                'attendance' => ($a = $attendance->get($p->collaborator_id)) ? [
                    'method' => $a->method,
                    'confirmed_at' => $a->confirmed_at?->toIso8601String(),
                    'label' => $a->label(),
                    'signature_path' => $a->signature_path,
                ] : null,
                'collaborator_id' => $p->collaborator_id,
                'labor_sector_id' => $p->labor_sector_id,
                'is_coordinator' => $coordKeys->has($p->collaborator_id . '-' . $p->labor_sector_id),
                'name' => $p->collaborator?->name,
                'nickname' => $p->collaborator?->nickname,
                'cpf' => $p->collaborator?->cpf,
                'office' => $p->sector?->name,
                'base_value' => $p->base_value,
                'extra_hours_value' => $p->extra_hours_value,
                'after_party_value' => $p->after_party_value,
                'coordination_value' => $p->coordination_value,
                'coordination_role' => $p->coordination_role,
                'clt_value' => $p->clt_value,
                'is_clt' => $p->collaborator?->employment_type === 'clt',
                'other_value' => $p->other_value,
                'total' => $p->total(),
                'notes' => $p->notes,
                'paid_at' => $p->paid_at?->toIso8601String(),
            ]);

        $event->loadMissing(['client:id,name', 'eventPlace:id,name', 'eventStatus:id,name']);

        return [
            'event' => $this->eventInfo($event),
            'rows' => $rows,
            'total' => round($rows->sum('total'), 2),
            'paid_total' => round($rows->whereNotNull('paid_at')->sum('total'), 2),
            'coordination_roles' => $this->coordinationRoles($event),
        ] + ['sections' => $this->reportSections($event, ['rows' => $rows])];
    }

    // ---- apoio ----

    private const COORD_SECTION = 'COORDENAÇÕES';

    /** Adicional pago a quem tem carteira assinada (CLT), por evento. */
    public const CLT_BONUS = 30.0;

    /** O adicional CLT não vale em degustação, experiência e coffee break (tipo ou nome do evento). */
    public static function cltBonusApplies(Event $event): bool
    {
        $event->loadMissing('eventType:id,name');
        $text = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(($event->eventType?->name ?? '') . ' ' . ($event->name ?? '')));
        foreach (['degust', 'experiencia', 'coffee'] as $word) {
            if (str_contains($text, $word)) {
                return false;
            }
        }
        return true;
    }

    /** Coordenador de um setor → linha da parte COORDENAÇÕES da Tabela de valores (adicional). */
    private function sectorCoordinationRole(?string $sectorName, ?LaborValueTable $table): ?string
    {
        $map = [
            'mesa' => 'MESA', 'copa / churrasqueiro' => 'COPA', 'copa' => 'COPA', 'cozinha' => 'COZINHA', 'portaria' => 'PORTARIA',
            'financeiro' => 'FINANCEIRO', 'gestor de experiência' => 'GESTOR DE EXPERIÊNCIA', 'fiscal' => 'FISCAL DE EVENTO',
            'coordenação geral' => 'COORDENADOR GERAL',
        ];
        $role = $map[mb_strtolower(trim((string) $sectorName))] ?? null;
        return $role && $table && in_array($role, $table->rowNames(self::COORD_SECTION), true) ? $role : null;
    }

    /** Setores que já são uma coordenação: quem é convocado neles recebe o adicional automaticamente. */
    private function defaultCoordinationRole(?string $sectorName, ?LaborValueTable $table): ?string
    {
        $map = ['coordenação geral' => 'COORDENADOR GERAL', 'fiscal' => 'FISCAL DE EVENTO', 'financeiro' => 'FINANCEIRO', 'gestor de experiência' => 'GESTOR DE EXPERIÊNCIA'];
        $role = $map[mb_strtolower(trim((string) $sectorName))] ?? null;
        return $role && $table && in_array($role, $table->rowNames(self::COORD_SECTION), true) ? $role : null;
    }

    private function coordinationValue(Event $event, string $role): ?float
    {
        $event->loadMissing(['eventPlace.address.city']);
        $hours = $event->duration !== null ? (float) $event->duration : null;
        return LaborValueTable::current()?->valueFor($role, $event->eventPlace?->address?->city?->name, null, $hours, self::COORD_SECTION);
    }

    /** Coordenações da Tabela de valores com o adicional na cidade do local do evento. */
    private function coordinationRoles(Event $event): array
    {
        $table = LaborValueTable::current();
        if (!$table) {
            return [];
        }
        return collect($table->rowNames(self::COORD_SECTION))
            ->map(fn ($name) => ['name' => $name, 'value' => (float) ($this->coordinationValue($event, $name) ?? 0)])
            ->all();
    }

    /** Setores da base (ativos) + setores criados nos eventos informados. */
    private function sectors($eventIds = [])
    {
        return LaborSector::where('active', true)
            ->where(fn ($q) => $q->whereNull('event_id')->orWhereIn('event_id', collect($eventIds)->all()))
            ->orderByRaw('event_id is not null')->orderBy('position')->orderBy('id')
            ->get(['id', 'event_id', 'name', 'color', 'has_transport', 'has_coordination', 'has_truck', 'position', 'value_table_role']);
    }

    private function monthEvents(Carbon $start, Carbon $end)
    {
        return Event::query()
            ->with(['client:id,name', 'eventPlace:id,name', 'eventPlace.address.city:id,name', 'eventStatus:id,name', 'collaborator:id,name'])
            ->whereHas('eventStatus', fn ($q) => $q->whereIn('name', self::STATUSES))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')->orderBy('time')
            ->get();
    }

    private function eventInfo(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'date' => $event->date?->format('Y-m-d'),
            'time' => $event->time,
            'duration' => $event->duration,
            'client' => $event->client?->name,
            'place' => $event->eventPlace?->name,
            'city' => $event->eventPlace?->address?->city?->name,
            'status' => $event->eventStatus?->name,
            'guests' => $event->event_guests,
            'consultant' => $event->collaborator?->name,
            'locked' => $this->isLocked($event),
        ];
    }

    /** Evento Concluído fica só para consulta na Mão de Obra (regra que pode ser desligada em Configurações). */
    private function isLocked(Event $event): bool
    {
        return $event->eventStatus?->name === 'Concluído' && PlanningRules::enabled('mao_de_obra_concluido_so_leitura');
    }

    private function monthRange(?string $month): array
    {
        try {
            $start = Carbon::createFromFormat('Y-m-d', ($month ?: now()->format('Y-m')) . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
        }
        return [$start, (clone $start)->endOfMonth()];
    }
}
