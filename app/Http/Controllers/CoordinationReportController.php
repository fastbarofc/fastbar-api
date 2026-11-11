<?php

namespace App\Http\Controllers;

use App\Models\CoordinationReport;
use App\Models\CoordinationReportType;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Planejamento > Relatório Coordenação (André, 2026-11-04): tabela dos eventos × tipos de relatório.
 * Cada relatório é um link do Google Drive (gerado pelo Google Forms); clicar baixa o arquivo.
 */
class CoordinationReportController extends Controller
{
    private const STATUSES = ['Fechado', 'Planejamento', 'Concluído'];

    public function index(Request $request)
    {
        $month = $request->input('month') ?: now()->format('Y-m');
        try {
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $start = now()->startOfMonth();
        }
        $end = (clone $start)->endOfMonth();

        $types = CoordinationReportType::orderBy('sort_order')->orderBy('id')->get();
        $events = Event::query()
            ->with(['client:id,name', 'eventPlace:id,name', 'eventStatus:id,name', 'collaborator:id,name'])
            ->whereHas('eventStatus', fn ($q) => $q->whereIn('name', self::STATUSES))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')->orderBy('time')
            ->get();
        $reports = CoordinationReport::with('user:id,name')->whereIn('event_id', $events->pluck('id'))->get()
            ->groupBy('event_id');

        $data = $events->map(function (Event $event) use ($reports) {
            $date = $event->date ? Carbon::parse($event->date) : null;
            // Prazo do fluxograma: relatório até segunda-feira às 10h depois do evento.
            $deadline = $date ? (clone $date)->next(Carbon::MONDAY)->setTime(10, 0) : null;
            return [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date?->format('Y-m-d'),
                'client' => $event->client?->name,
                'place' => $event->eventPlace?->name,
                'status' => $event->eventStatus?->name,
                'consultant' => $event->collaborator?->name,
                'deadline' => $deadline?->toIso8601String(),
                'past' => $date ? $date->lt(now()->startOfDay()) || $date->isToday() : false,
                'reports' => ($reports[$event->id] ?? collect())->mapWithKeys(fn (CoordinationReport $r) => [
                    $r->coordination_report_type_id => [
                        'id' => $r->id,
                        'drive_url' => $r->drive_url,
                        'download_url' => self::downloadUrl($r->drive_url),
                        'received_at' => $r->received_at?->toIso8601String(),
                        'on_time' => $deadline && $r->received_at ? $r->received_at->lte($deadline) : null,
                        'notes' => $r->notes,
                        'user_name' => $r->user?->name,
                    ],
                ]),
            ];
        })->values();

        return response()->json(['data' => $data, 'types' => $types, 'month' => $start->format('Y-m')]);
    }

    /** Link que baixa direto (Google Docs vira PDF; (file/d/ID/... ou open?id=ID viram uc?export=download&id=ID). */
    public static function downloadUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        // Relatório em Google Docs: baixa em PDF.
        if (preg_match('~docs\.google\.com/document/d/([A-Za-z0-9_-]+)~', $url, $m)) {
            return 'https://docs.google.com/document/d/' . $m[1] . '/export?format=pdf';
        }
        if (preg_match('~drive\.google\.com/(?:file/d/|open\?id=|uc\?(?:.*&)?id=)([A-Za-z0-9_-]+)~', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }
        return $url;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'coordination_report_type_id' => 'required|exists:coordination_report_types,id',
            'drive_url' => 'required|url|max:1000',
            'received_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        $report = CoordinationReport::updateOrCreate(
            ['event_id' => $data['event_id'], 'coordination_report_type_id' => $data['coordination_report_type_id']],
            [
                'drive_url' => $data['drive_url'],
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()?->id,
            ]
        );
        return response()->json(['data' => $report], 201);
    }

    public function destroy(CoordinationReport $coordinationReport)
    {
        $coordinationReport->delete();
        return response()->json(null, 204);
    }

    // ---- Tipos de relatório ----

    public function types()
    {
        return response()->json(['data' => CoordinationReportType::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function storeType(Request $request)
    {
        $data = $this->validateType($request);
        $data['sort_order'] = (int) CoordinationReportType::max('sort_order') + 1;
        return response()->json(['data' => CoordinationReportType::create($data)], 201);
    }

    public function updateType(Request $request, CoordinationReportType $coordinationReportType)
    {
        $coordinationReportType->update($this->validateType($request));
        return response()->json(['data' => $coordinationReportType]);
    }

    public function destroyType(CoordinationReportType $coordinationReportType)
    {
        if (CoordinationReport::where('coordination_report_type_id', $coordinationReportType->id)->exists()) {
            return response()->json(['message' => 'Este tipo já tem relatórios lançados. Exclua os relatórios antes.'], 422);
        }
        $coordinationReportType->delete();
        return response()->json(null, 204);
    }

    private function validateType(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'form_url' => 'nullable|url|max:1000',
        ]);
    }
}
