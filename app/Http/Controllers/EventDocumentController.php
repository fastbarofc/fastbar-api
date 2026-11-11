<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Aba Documentos do evento: histórico de orçamentos, contratos e aditivos. */
class EventDocumentController extends Controller
{
    public function index(Event $event)
    {
        $documents = EventDocument::with('user.collaborator')->where('event_id', $event->id)->orderBy('id')->get();

        return response()->json(['data' => $documents->map(fn (EventDocument $doc) => self::present($doc))->values()]);
    }

    public function updateStatus(Request $request, EventDocument $eventDocument)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(EventDocument::STATUSES)],
        ]);

        // No Fastbar o aditivo já muda o evento na hora em que é gerado.
        if ($eventDocument->type === 'aditivo' && $data['status'] === 'cancelado') {
            return response()->json(['message' => 'O aditivo já mudou o evento e não pode ser cancelado. Faça outro aditivo para desfazer.'], 422);
        }

        $eventDocument->update([
            'status' => $data['status'],
            'decided_at' => in_array($data['status'], ['assinado', 'aprovado', 'recusado', 'cancelado'], true) ? now() : null,
        ]);
        $eventDocument->load('user.collaborator');

        return response()->json(['data' => self::present($eventDocument)]);
    }

    public static function present(EventDocument $doc): array
    {
        return [
            'id' => $doc->id,
            'type' => $doc->type,
            'number' => $doc->number,
            'code' => $doc->code,
            'title' => $doc->title(),
            'status' => $doc->status,
            'total_value' => $doc->total_value !== null ? (float) $doc->total_value : null,
            'guests' => $doc->guests,
            'changes' => $doc->changes ?? [],
            'observation' => $doc->observation,
            'pdf_url' => $doc->pdf_path ? asset('storage/' . $doc->pdf_path) : null,
            'source_id' => $doc->source_id,
            'user' => $doc->user?->collaborator?->name ?? $doc->user?->name,
            'created_at' => $doc->created_at,
            'decided_at' => $doc->decided_at,
        ];
    }
}
