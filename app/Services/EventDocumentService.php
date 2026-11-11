<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Guarda cada orçamento, contrato e aditivo gerado no histórico do evento.
 * Numeração igual à do Parthenon: orçamento "ID/1", "ID/2"; contrato
 * "ID/<último orçamento válido>"; aditivo "ID/<orçamento do contrato>-A1"...
 */
class EventDocumentService
{
    private const SNAPSHOT_LABELS = [
        'date' => 'Data',
        'time' => 'Horário',
        'place' => 'Local',
        'guests' => 'Convidados',
        'guest_unit_value' => 'Valor por convidado',
        'total_value' => 'Valor total',
    ];

    /**
     * Cria a linha do histórico e copia o PDF recém-gerado para um arquivo só
     * deste documento (o gerador sobrescreve sempre o mesmo nome de arquivo).
     */
    public static function record(Event $event, string $type, ?string $pdfUrl, ?string $sourceType = null, ?int $sourceId = null, ?string $observation = null): ?EventDocument
    {
        try {
            $event = $event->fresh(['eventPlace', 'eventBatches']);
            $number = (int) EventDocument::withTrashed()->where('event_id', $event->id)->where('type', $type)->max('number') + 1;
            $snapshot = self::snapshot($event);
            $previous = EventDocument::where('event_id', $event->id)->latest('id')->first();

            return EventDocument::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'type' => $type,
                'number' => $number,
                'code' => self::code($event, $type, $number),
                'status' => $type === 'orcamento' ? 'enviado' : 'gerado',
                'total_value' => $snapshot['total_value'],
                'guests' => $snapshot['guests'],
                'snapshot' => $snapshot,
                'changes' => $previous ? self::changes($previous->snapshot ?? [], $snapshot) : [],
                'pdf_path' => self::keepPdf($pdfUrl, $event, $type, $number),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'observation' => $observation,
            ]);
        } catch (\Throwable $e) {
            // O histórico nunca pode impedir o PDF de sair.
            report($e);
            return null;
        }
    }

    private static function code(Event $event, string $type, int $number): string
    {
        if ($type === 'orcamento') {
            return "{$event->id}/{$number}";
        }

        $budgets = EventDocument::where('event_id', $event->id)->where('type', 'orcamento');
        $budget = (clone $budgets)->whereNotIn('status', ['cancelado', 'recusado'])->max('number')
            ?? $budgets->max('number')
            ?? 0;

        // Sem orçamento guardado (eventos antigos): só o número do evento.
        $base = $budget ? "{$event->id}/{$budget}" : (string) $event->id;

        return $type === 'contrato' ? $base : "{$base}-A{$number}";
    }

    public static function snapshot(Event $event): array
    {
        $totals = $event->calculatedTotals();

        return [
            'date' => $event->date ? Carbon::parse($event->date)->format('d/m/Y') : null,
            'time' => $event->time ? substr((string) $event->time, 0, 5) : null,
            'place' => $event->eventPlace?->name,
            'guests' => (int) $event->event_guests,
            'guest_unit_value' => round((float) $event->guest_unit_value, 2),
            'total_value' => round((float) ($totals['total_value'] ?? $event->total_value), 2),
        ];
    }

    private static function changes(array $before, array $after): array
    {
        $changes = [];
        foreach (self::SNAPSHOT_LABELS as $key => $label) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if ($old == $new) {
                continue;
            }
            if (in_array($key, ['guest_unit_value', 'total_value'], true)) {
                $old = 'R$ ' . number_format((float) $old, 2, ',', '.');
                $new = 'R$ ' . number_format((float) $new, 2, ',', '.');
            }
            $changes[] = "{$label}: " . ($old ?? '—') . ' → ' . ($new ?? '—');
        }
        return $changes;
    }

    private static function keepPdf(?string $pdfUrl, Event $event, string $type, int $number): ?string
    {
        if (!$pdfUrl) {
            return null;
        }
        $source = storage_path('app/public/pdfs/' . basename((string) parse_url($pdfUrl, PHP_URL_PATH)));
        if (!File::exists($source)) {
            return null;
        }
        $directory = storage_path('app/public/pdfs/documentos');
        File::ensureDirectoryExists($directory);
        $name = "{$type}-{$event->id}-{$number}-" . Str::lower(Str::random(6)) . '.pdf';
        File::copy($source, "{$directory}/{$name}");

        return "pdfs/documentos/{$name}";
    }
}
