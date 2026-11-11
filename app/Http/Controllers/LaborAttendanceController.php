<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventAttendanceLink;
use App\Models\EventLaborAttendance;
use App\Models\EventLaborConvocation;
use App\Models\EventLaborPayment;
use App\Services\FileService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Presença no evento pelo celular do coordenador (André, 2026-11-09, opção A):
 * o rosto do convocado é comparado com a foto do cadastro no próprio celular (nada vai para serviço de fora);
 * quem não aceitou o uso da foto assina com o dedo na tela. Substitui a assinatura do Relatório Financeiro.
 */
class LaborAttendanceController extends Controller
{
    /** Convocados do evento (um por pessoa), com foto, aceite, valor a receber e presença. */
    public function index(Event $event)
    {
        app(LaborConvocationController::class)->syncPaymentRows($event);

        return response()->json(['data' => $this->payload($event)]);
    }

    /**
     * Grava uma ou mais presenças (o celular guarda as que ficaram sem internet e manda todas juntas depois).
     * items[]: collaborator_id, method (face|signature), match_score, confirmed_at (hora no celular), signature (imagem).
     */
    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:300',
            'items.*.collaborator_id' => 'required|integer',
            'items.*.method' => 'required|in:face,signature',
            'items.*.match_score' => 'nullable|numeric|min:0|max:1',
            'items.*.confirmed_at' => 'nullable|date',
            'items.*.signature' => 'nullable|string',
        ]);

        $convocated = EventLaborConvocation::where('event_id', $event->id)->pluck('collaborator_id')->flip();
        $consents = Collaborator::whereIn('id', collect($data['items'])->pluck('collaborator_id'))->pluck('biometric_consent_at', 'id');
        $saved = [];
        $skipped = [];
        foreach ($data['items'] as $item) {
            $id = (int) $item['collaborator_id'];
            if (!$convocated->has($id)) {
                $skipped[] = ['collaborator_id' => $id, 'reason' => 'Não está convocado neste evento.'];
                continue;
            }
            if ($item['method'] === 'face' && empty($consents[$id])) {
                $skipped[] = ['collaborator_id' => $id, 'reason' => 'Sem aceite do uso da foto: confirme pela assinatura.'];
                continue;
            }
            if ($item['method'] === 'signature' && empty($item['signature'])) {
                $skipped[] = ['collaborator_id' => $id, 'reason' => 'Assinatura em branco.'];
                continue;
            }
            $existing = EventLaborAttendance::where('event_id', $event->id)->where('collaborator_id', $id)->first();
            if ($existing) {
                // Já confirmado: a primeira confirmação vale (o celular pode mandar a mesma de novo).
                $saved[] = $id;
                continue;
            }
            $signaturePath = null;
            if ($item['method'] === 'signature') {
                $signaturePath = FileService::saveImage('presenca-assinaturas', $item['signature'])->path;
            }
            $confirmedAt = null;
            try {
                $confirmedAt = !empty($item['confirmed_at']) ? Carbon::parse($item['confirmed_at'])->setTimezone(config('app.timezone')) : null;
            } catch (\Throwable $e) {
                $confirmedAt = null;
            }
            if (!$confirmedAt || $confirmedAt->isFuture()) {
                $confirmedAt = now();
            }
            EventLaborAttendance::create([
                'event_id' => $event->id,
                'collaborator_id' => $id,
                'method' => $item['method'],
                'match_score' => $item['method'] === 'face' ? ($item['match_score'] ?? null) : null,
                'signature_path' => $signaturePath,
                'confirmed_at' => $confirmedAt,
                'created_by' => $request->user()?->id,
            ]);
            $saved[] = $id;
        }

        return response()->json(['data' => $this->payload($event), 'saved' => $saved, 'skipped' => $skipped]);
    }

    /** Desfaz uma presença marcada por engano. */
    public function destroy(Event $event, Collaborator $collaborator)
    {
        $row = EventLaborAttendance::where('event_id', $event->id)->where('collaborator_id', $collaborator->id)->first();
        if ($row) {
            if ($row->signature_path) {
                Storage::disk('public')->delete($row->signature_path);
            }
            $row->delete();
        }

        return response()->json(['data' => $this->payload($event)]);
    }

    /** Foto do cadastro tirada na hora pelo coordenador + aceite do colaborador (primeiro evento da pessoa). */
    public function enroll(Request $request, Event $event, Collaborator $collaborator)
    {
        $data = $request->validate([
            'photo' => 'required|string',
            'consent' => 'required|accepted',
        ]);
        abort_unless(EventLaborConvocation::where('event_id', $event->id)->where('collaborator_id', $collaborator->id)->exists(), 422, 'Não está convocado neste evento.');

        $old = $collaborator->file;
        $file = FileService::saveImage('collaborators', $data['photo']);
        $collaborator->update([
            'file_id' => $file->id,
            'biometric_consent_at' => $collaborator->biometric_consent_at ?? now(),
            'biometric_consent_by' => $collaborator->biometric_consent_at ? $collaborator->biometric_consent_by : $request->user()?->id,
        ]);
        if ($old) {
            Storage::disk('public')->delete($old->path);
            $old->delete();
        }

        return response()->json(['data' => $this->payload($event)]);
    }

    /** Foto do cadastro pela API (com login), para o celular comparar o rosto. */
    public function photo(Event $event, Collaborator $collaborator)
    {
        abort_unless(EventLaborConvocation::where('event_id', $event->id)->where('collaborator_id', $collaborator->id)->exists(), 404);
        $path = $collaborator->file?->path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), ['Cache-Control' => 'private, max-age=86400']);
    }

    // ---- Link do evento para o coordenador (sem login) ----

    public function showLink(Event $event)
    {
        $link = EventAttendanceLink::where('event_id', $event->id)->first();
        return response()->json(['data' => $link?->toInfo(), 'default' => $this->defaultWindow($event)]);
    }

    /** Cria o link (ou troca as datas). new_token=true gera um link novo e o antigo para de funcionar. */
    public function saveLink(Request $request, Event $event)
    {
        $data = $request->validate([
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'new_token' => 'nullable|boolean',
        ]);
        $default = $this->defaultWindow($event);
        $link = EventAttendanceLink::firstOrNew(['event_id' => $event->id]);
        if (!$link->exists || $request->boolean('new_token')) {
            $link->token = Str::random(48);
        }
        $link->valid_from = $data['valid_from'] ?? ($link->valid_from ?? $default['valid_from']);
        $link->valid_until = $data['valid_until'] ?? ($link->valid_until ?? $default['valid_until']);
        $link->created_by = $request->user()?->id;
        $link->save();

        return response()->json(['data' => $link->toInfo()]);
    }

    public function destroyLink(Event $event)
    {
        EventAttendanceLink::where('event_id', $event->id)->delete();
        return response()->json(['data' => null]);
    }

    public function publicIndex(string $token)
    {
        return $this->index($this->eventFromToken($token));
    }

    public function publicStore(Request $request, string $token)
    {
        return $this->store($request, $this->eventFromToken($token));
    }

    public function publicDestroy(string $token, Collaborator $collaborator)
    {
        return $this->destroy($this->eventFromToken($token), $collaborator);
    }

    public function publicEnroll(Request $request, string $token, Collaborator $collaborator)
    {
        return $this->enroll($request, $this->eventFromToken($token), $collaborator);
    }

    public function publicPhoto(string $token, Collaborator $collaborator)
    {
        return $this->photo($this->eventFromToken($token), $collaborator);
    }

    private function eventFromToken(string $token): Event
    {
        $link = strlen($token) >= 32 ? EventAttendanceLink::with('event')->where('token', $token)->first() : null;
        abort_unless($link && $link->event, 404, 'Link não encontrado ou cancelado. Peça um link novo ao Planejamento.');
        abort_unless($link->isActive(), 403, 'Este link vale de ' . $link->valid_from->format('d/m/Y') . ' até ' . $link->valid_until->format('d/m/Y') . '.');
        return $link->event;
    }

    /** Padrão: do dia anterior ao dia seguinte do evento. */
    private function defaultWindow(Event $event): array
    {
        $date = $event->date ? Carbon::parse($event->date) : now();
        return ['valid_from' => $date->copy()->subDay()->format('Y-m-d'), 'valid_until' => $date->copy()->addDay()->format('Y-m-d')];
    }

    private function payload(Event $event): array
    {
        $event->loadMissing(['client:id,name', 'eventPlace:id,name', 'eventStatus:id,name']);
        $convocations = EventLaborConvocation::with(['collaborator.file', 'sector:id,name,position'])
            ->where('event_id', $event->id)->get();
        $payments = EventLaborPayment::where('event_id', $event->id)->get()->groupBy('collaborator_id');
        $attendance = EventLaborAttendance::where('event_id', $event->id)->get()->keyBy('collaborator_id');

        $people = $convocations->groupBy('collaborator_id')->map(function ($rows, $id) use ($payments, $attendance) {
            $c = $rows->first()->collaborator;
            if (!$c) {
                return null;
            }
            $sorted = $rows->sortBy(fn ($r) => $r->sector?->position ?? 99);
            $a = $attendance->get($id);
            return [
                'id' => $c->id,
                'name' => $c->name,
                'nickname' => $c->nickname,
                'sector' => $sorted->map(fn ($r) => $r->sector?->name)->filter()->unique()->implode(', '),
                'sector_position' => $sorted->first()->sector?->position ?? 99,
                'is_coordinator' => $rows->contains(fn ($r) => $r->is_coordinator),
                'has_photo' => (bool) $c->file_id,
                'photo_version' => $c->file_id,
                'consent' => (bool) $c->biometric_consent_at,
                'value' => round(($payments->get($id) ?? collect())->sum(fn ($p) => $p->total()), 2),
                'attendance' => $a ? [
                    'method' => $a->method,
                    'match_score' => $a->match_score,
                    'confirmed_at' => $a->confirmed_at?->toIso8601String(),
                    'signature_url' => $a->signatureUrl(),
                ] : null,
            ];
        })->filter()->sortBy(fn ($p) => [$p['sector_position'], $p['is_coordinator'] ? 0 : 1, $p['name']])->values();

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date?->format('Y-m-d'),
                'time' => $event->time,
                'client' => $event->client?->name,
                'place' => $event->eventPlace?->name,
                'status' => $event->eventStatus?->name,
            ],
            'people' => $people,
            'confirmed' => $people->whereNotNull('attendance')->count(),
        ];
    }
}
