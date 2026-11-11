<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventDocument;
use App\Models\EventPlanningPerformance;
use App\Models\EventStatus;
use App\Models\EventStatusHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

/**
 * Planejamento > Desempenho (André, 2026-11-05) — a planilha "Prazos de liberação" no sistema.
 * Liberado = dia em que o evento foi liberado para o Planejamento (saiu de Fechado; André 2026-11-05).
 * Prazo = 30 dias antes da data do evento.
 * Trocas fora do prazo = aditivos feitos a menos de 30 dias do evento ou depois de Concluído.
 */
class PlanningPerformanceController extends Controller
{
    private const STATUSES = ['Fechado', 'Planejamento', 'Concluído'];

    /** Prazo máximo para o evento ficar Concluído: 30 dias antes do evento (André 2026-11-05). */
    private const DAYS = 30;

    public function index(Request $request)
    {
        $month = $request->input('month') ?: now()->format('Y-m');
        try {
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
        }
        $end = (clone $start)->endOfMonth();

        $events = Event::query()
            ->with(['client:id,name', 'eventPlace:id,name', 'eventStatus:id,name', 'collaborator:id,name'])
            ->whereHas('eventStatus', fn ($q) => $q->whereIn('name', self::STATUSES))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')->orderBy('time')
            ->get();
        $ids = $events->pluck('id');

        $manual = EventPlanningPerformance::whereIn('event_id', $ids)->get()->keyBy('event_id');
        $autoReleased = $this->releasedDates($ids->all(), 'Concluído');
        $autoPlanning = $this->releasedDates($ids->all(), 'Planejamento');
        // Dia em que o evento foi Fechado: fechado já dentro dos 30 dias = exceção aprovada pelo Planejamento, fica fora da conta.
        $autoClosed = $this->releasedDates($ids->all(), 'Fechado');
        // Aditivos vêm da aba Documentos do evento (tabela event_documents); sem ela, fica sem trocas.
        $addenda = class_exists(EventDocument::class) && \Illuminate\Support\Facades\Schema::hasTable('event_documents')
            ? EventDocument::whereIn('event_id', $ids)->where('type', 'aditivo')->where('status', '!=', 'cancelado')->orderBy('created_at')->get()->groupBy('event_id')
            : collect();
        $today = now()->startOfDay();
        // Staff = lanche cobrado das pessoas terceirizadas que trabalham no evento: venda extra com "staff" na descrição.
        $staffSales = \App\Models\ExtraSale::whereIn('event_id', $ids)->where('description', 'like', '%staff%')->get()->groupBy('event_id');

        $rows = $events->map(function (Event $event) use ($manual, $autoReleased, $autoPlanning, $autoClosed, $addenda, $today, $staffSales) {
            $date = Carbon::parse($event->date)->startOfDay();
            $deadline = (clone $date)->subDays(self::DAYS);
            $m = $manual->get($event->id);
            $auto = $autoReleased[$event->id] ?? null;
            $released = $m?->released_at ?? $auto; // dia em que ficou Concluído (usado nas trocas)
            $status = $event->eventStatus?->name;

            // Liberado para o Planejamento (como a gerente usa a planilha): dia em que saiu de Fechado para Planejamento.
            $planningAuto = $autoPlanning[$event->id] ?? ($status === 'Concluído' ? $auto : null);
            $planningAt = $planningAuto ?? $m?->planning_released_at; // automático (troca de status); a data manual antiga só vale se o sistema não tiver o registro
            $planningSituation = $planningAt
                ? ($planningAt->lte($deadline) ? 'no_prazo' : 'atrasado')
                : ($status === 'Fechado' ? ($today->gt($deadline) ? 'atrasado_aberto' : 'aguardando') : 'sem_data');
            $closedAt = $autoClosed[$event->id] ?? null;
            $lateClosing = $closedAt && $closedAt->gt($deadline);
            if ($lateClosing) {
                $planningSituation = 'excecao';
            }

            $changes = ($addenda[$event->id] ?? collect())
                ->filter(fn (EventDocument $d) => $d->created_at && ($d->created_at->copy()->startOfDay()->gt($deadline)
                    || ($released && $d->created_at->copy()->startOfDay()->gte($released))))
                ->values()
                ->map(fn (EventDocument $d) => [
                    'id' => $d->id,
                    'code' => $d->code(),
                    'date' => $d->created_at->format('Y-m-d'),
                    'days_before' => (int) $d->created_at->copy()->startOfDay()->diffInDays($date, false),
                    'status' => $d->status,
                    'after_release' => (bool) ($released && $d->created_at->copy()->startOfDay()->gte($released)),
                    'inside_30' => $d->created_at->copy()->startOfDay()->gt($deadline),
                    'items' => collect($d->changes ?? [])
                        ->filter(fn ($c) => is_string($c) && !str_starts_with($c, 'Diferença:') && !str_starts_with($c, 'Pagamento:'))
                        ->values()->all(),
                ])->all();

            $sales = $staffSales->get($event->id, collect());
            $staffAuto = $sales->isNotEmpty()
                ? $sales->map(fn ($x) => $x->description . ' (' . $x->quantity . ' × R$ ' . number_format(self::money($x->unit_value), 2, ',', '.') . ')')->implode('; ')
                : null;

            return [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $date->format('Y-m-d'),
                'deadline' => $deadline->format('Y-m-d'),
                'client' => $event->client?->name,
                'place' => $event->eventPlace?->name,
                'status' => $status,
                'consultant' => $event->collaborator?->name,
                // Liberado = liberado para o Planejamento (decisão do André 2026-11-05, como a gerente usa a planilha).
                'released_at' => $planningAt?->format('Y-m-d'),
                'released_auto' => $planningAuto?->format('Y-m-d'),
                'released_manual' => !$planningAuto && $m?->planning_released_at,
                'situation' => $planningSituation,
                'days_before' => $planningAt ? (int) $planningAt->diffInDays($date, false) : null,
                'concluded_at' => $released?->format('Y-m-d'),
                'staff' => (bool) $m?->staff || $staffAuto !== null,
                'staff_auto' => $staffAuto,
                'notes' => $m?->notes,
                'late_changes' => $changes,
                'closed_at' => $closedAt?->format('Y-m-d'),
                'excluded' => $lateClosing,
            ];
        })->values();

        // Eventos fechados com menos de 30 dias aparecem na lista, mas não entram nos números de desempenho.
        $all = $rows;
        $rows = $all->where('excluded', false)->values();
        $total = $rows->count();
        $pct = fn ($n) => $total ? round($n / $total * 100, 1) : null;
        $onTime = $rows->where('situation', 'no_prazo')->count();
        $staff = $rows->where('staff', true)->count();
        $withChanges = $rows->filter(fn ($r) => count($r['late_changes']) > 0)->count();

        $byConsultant = $rows->groupBy(fn ($r) => $r['consultant'] ?: 'Sem responsável')->map(function ($rs, $name) {
            $n = $rs->count();
            $ok = $rs->where('situation', 'no_prazo')->count();
            return [
                'name' => $name,
                'events' => $n,
                'on_time' => $ok,
                'late' => $rs->whereIn('situation', ['atrasado', 'atrasado_aberto'])->count(),
                'on_time_pct' => $n ? round($ok / $n * 100, 1) : null,
                'late_changes' => $rs->sum(fn ($r) => count($r['late_changes'])),
            ];
        })->sortByDesc('on_time_pct')->values();

        return response()->json([
            'data' => $all,
            'month' => $start->format('Y-m'),
            'summary' => [
                'events' => $total,
                'excluded' => $all->count() - $total,
                'on_time' => $onTime,
                'on_time_pct' => $pct($onTime),
                'late' => $rows->whereIn('situation', ['atrasado', 'atrasado_aberto'])->count(),
                'waiting' => $rows->where('situation', 'aguardando')->count(),
                'staff' => $staff,
                'staff_pct' => $pct($staff),
                'events_with_late_changes' => $withChanges,
                'late_changes_pct' => $pct($withChanges),
                'late_changes' => $rows->sum(fn ($r) => count($r['late_changes'])),
            ],
            'by_consultant' => $byConsultant,
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'released_at' => 'nullable|date',
            'planning_released_at' => 'nullable|date',
            'staff' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);
        if (array_key_exists('released_at', $data)) {
            // Na tela, "Liberado" é a liberação para o Planejamento.
            $data['planning_released_at'] = $data['released_at'];
            unset($data['released_at']);
        }
        $row = EventPlanningPerformance::firstOrNew(['event_id' => $event->id]);
        foreach (['planning_released_at', 'released_at', 'staff', 'notes'] as $k) {
            if (array_key_exists($k, $data)) {
                $row->{$k} = $k === 'staff' ? (bool) $data[$k] : $data[$k];
            }
        }
        $row->updated_by = $request->user()?->id;
        $row->save();

        return response()->json(['data' => ['ok' => true]]);
    }

    private static function money($v): float
    {
        $v = (string) $v;
        return (float) (str_contains($v, ',') ? str_replace(',', '.', str_replace('.', '', $v)) : $v);
    }

    /** Primeiro dia em que cada evento passou para o status informado (histórico de alterações do evento). */
    private function releasedDates(array $eventIds, string $statusName): array
    {
        $planning = EventStatus::where('name', $statusName)->value('id');
        if (!$planning || !$eventIds) {
            return [];
        }
        $out = [];
        Audit::where('auditable_type', Event::class)->whereIn('auditable_id', $eventIds)
            ->where('new_values', 'like', '%event_status_id%')->orderBy('created_at')
            ->get(['auditable_id', 'new_values', 'created_at'])
            ->each(function ($a) use (&$out, $planning) {
                $new = is_array($a->new_values) ? $a->new_values : json_decode((string) $a->new_values, true);
                if ((int) ($new['event_status_id'] ?? 0) === (int) $planning && !isset($out[$a->auditable_id])) {
                    $out[$a->auditable_id] = Carbon::parse($a->created_at)->startOfDay();
                }
            });
        EventStatusHistory::whereIn('event_id', $eventIds)->where('event_status_id', $planning)->orderBy('created_at')->get()
            ->each(function ($h) use (&$out) {
                if (!isset($out[$h->event_id]) && $h->created_at) {
                    $out[$h->event_id] = Carbon::parse($h->created_at)->startOfDay();
                }
            });

        return $out;
    }
}
