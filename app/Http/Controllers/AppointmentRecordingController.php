<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Jobs\SummarizeMeeting;
use App\Models\AppSetting;
use App\Services\MeetingSummaryService;
use App\Models\Appointment;
use App\Models\AppointmentRecording;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Partner;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Gravação de reuniões. O navegador grava o áudio e manda em pedaços (a cada poucos segundos),
 * assim não esbarra no limite de tamanho de envio do PHP e não perde tudo se a página fechar.
 */
class AppointmentRecordingController extends Controller
{
    private const DISK = 'local';

    /** Começa uma gravação vazia para o compromisso. */
    public function start(Request $request, Appointment $appointment)
    {
        if (!$this->canSee($request, $appointment)) {
            return $this->deny();
        }

        $request->validate(['mime_type' => 'nullable|string|max:100']);

        $mime = $request->input('mime_type') ?: 'audio/webm';
        $extension = str_contains($mime, 'ogg') ? 'ogg' : (str_contains($mime, 'mp4') ? 'm4a' : 'webm');
        $path = 'appointment-recordings/' . $appointment->id . '/' . uniqid('reuniao-', false) . '.' . $extension;
        Storage::disk(self::DISK)->put($path, '');

        $recording = AppointmentRecording::create([
            'appointment_id' => $appointment->id,
            'path' => $path,
            'mime_type' => explode(';', $mime)[0],
            'status' => AppointmentRecording::STATUS_RECORDING,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['id' => $recording->id], 201);
    }

    /** Junta mais um pedaço do áudio no fim do arquivo. */
    public function chunk(Request $request, AppointmentRecording $recording)
    {
        if ($recording->created_by !== $request->user()->id || $recording->status !== AppointmentRecording::STATUS_RECORDING) {
            return response()->json(['message' => 'Esta gravação não está mais aberta.'], 422);
        }

        $request->validate(['chunk' => 'required|file|max:20480']);

        $target = Storage::disk(self::DISK)->path($recording->path);
        $out = fopen($target, 'ab');
        $in = fopen($request->file('chunk')->getRealPath(), 'rb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        clearstatcache(true, $target);
        $recording->update(['size' => filesize($target)]);

        return response()->json(['size' => $recording->size], 200);
    }

    /** Fecha a gravação. */
    public function finish(Request $request, AppointmentRecording $recording)
    {
        if ($recording->created_by !== $request->user()->id) {
            return $this->deny();
        }

        $request->validate(['duration_seconds' => 'nullable|integer|min:0']);

        if ($recording->size === 0) {
            Storage::disk(self::DISK)->delete($recording->path);
            $recording->forceDelete();
            return response()->json(['message' => 'A gravação ficou vazia e foi descartada.'], 200);
        }

        $recording->update([
            'status' => AppointmentRecording::STATUS_DONE,
            'duration_seconds' => $request->input('duration_seconds'),
        ]);

        $appointment = $recording->appointment;
        $appointment->update([
            'end_time' => $appointment->end_time ?: now()->format('H:i'),
            'summary_status' => 'pending',
            'summary_error' => null,
        ]);

        SummarizeMeeting::dispatchAfterResponse($appointment->id);

        return response()->json(['id' => $recording->id], 200);
    }

    /** Toca/baixa o áudio (o navegador busca com o login e monta o player). */
    public function audio(Request $request, AppointmentRecording $recording)
    {
        if (!Storage::disk(self::DISK)->exists($recording->path)) {
            abort(404, 'Arquivo da gravação não encontrado.');
        }

        $client = $recording->appointment?->client;
        $name = 'Reuniao' . ($client ? ' - ' . $client->name : '') . ' - ' . $recording->created_at->format('d-m-Y H\hi')
            . '.' . pathinfo($recording->path, PATHINFO_EXTENSION);

        return response()->file(Storage::disk(self::DISK)->path($recording->path), [
            'Content-Type' => $recording->mime_type ?: 'audio/webm',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $name) . '"',
        ]);
    }

    public function destroy(Request $request, AppointmentRecording $recording)
    {
        $appointment = $recording->appointment;
        if ($recording->created_by !== $request->user()->id && !$request->user()->isAdmin()
            && $appointment?->created_by !== $request->user()->id) {
            return $this->deny();
        }

        Storage::disk(self::DISK)->delete($recording->path);
        $recording->delete();

        return response()->json([], 204);
    }

    /** Tenta o resumo de novo (ex.: depois de informar a chave da IA). */
    public function summarize(Request $request, Appointment $appointment)
    {
        if (!$this->canSee($request, $appointment)) {
            return $this->deny();
        }

        if (!$appointment->recordings()->where('status', AppointmentRecording::STATUS_DONE)->exists()) {
            return response()->json(['message' => 'Não há áudio desta reunião para resumir.'], 422);
        }

        $appointment->update(['summary_status' => 'pending', 'summary_error' => null]);
        SummarizeMeeting::dispatchAfterResponse($appointment->id);

        return response()->json(new AppointmentResource($appointment->fresh()), 200);
    }

    /** Diz se as chaves da IA das reuniões estão informadas (nunca devolve as chaves). */
    public function aiSettings()
    {
        return response()->json([
            'openai_configured' => (bool) MeetingSummaryService::openAiKey(),
            'anthropic_configured' => (bool) MeetingSummaryService::anthropicKey(),
        ], 200);
    }

    public function updateAiSettings(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'nullable|string|max:500',
            'anthropic_api_key' => 'nullable|string|max:500',
        ]);

        if ($request->filled('openai_api_key')) {
            AppSetting::putSecret(MeetingSummaryService::OPENAI_KEY, trim($request->input('openai_api_key')));
        }
        if ($request->filled('anthropic_api_key')) {
            AppSetting::putSecret(MeetingSummaryService::ANTHROPIC_KEY, trim($request->input('anthropic_api_key')));
        }

        return $this->aiSettings();
    }

    /** Reuniões do cliente, mais novas primeiro, com as gravações. */
    /**
     * Próximas reuniões pelo Meet da pessoa (criadas por ela ou em que foi convidada), da mais perto para a mais longe.
     * Inclui as de hoje que ainda não terminaram. Administrador vê as de todo mundo.
     */
    public function upcomingMeetings(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');
        $nowTime = now()->subMinutes(30)->format('H:i:s');

        $appointments = Appointment::query()
            ->whereNotNull('meeting_url')
            ->where(function ($q) use ($today, $nowTime) {
                $q->where('date', '>', $today)
                    ->orWhere(function ($q2) use ($today, $nowTime) {
                        $q2->where('date', $today)->where(function ($q3) use ($nowTime) {
                            $q3->whereNull('end_time')->orWhere('end_time', '>=', $nowTime);
                        });
                    });
            })
            ->when(!$user->isAdmin(), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('created_by', $user->id)
                        ->orWhereHas('users', fn ($q3) => $q3->where('users.id', $user->id));
                });
            })
            ->with(['users:id,name', 'creator:id,name', 'client:id,name', 'partner:id,name', 'supplier:id,corporate_name,fantasy_name', 'participants', 'recordings'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(30)
            ->get();

        return response()->json(['data' => AppointmentResource::collection($appointments)], 200);
    }

    /**
     * Cria o link do Meet na conta Google de quem criou a reunião (imediata ou agendada).
     * Usa a data e o horário da reunião; a duração vem do pedido (padrão 1 hora).
     */
    public function createGoogleMeet(Request $request, Appointment $appointment)
    {
        if (!$this->canSee($request, $appointment)) {
            return $this->deny();
        }
        $data = $request->validate(['duration_minutes' => 'nullable|integer|min:15|max:480']);

        $account = \App\Models\GoogleAccount::where('user_id', $request->user()->id)->first();
        if (!$account) {
            return response()->json(['message' => 'Conecte o seu Google em Minha conta para o link do Meet aparecer sozinho.', 'code' => 'google_not_connected'], 422);
        }

        $start = \Carbon\Carbon::parse($appointment->date->format('Y-m-d') . ' ' . ($appointment->start_time ? substr($appointment->start_time, 0, 5) : now()->format('H:i')));
        $end = $appointment->end_time
            ? \Carbon\Carbon::parse($appointment->date->format('Y-m-d') . ' ' . substr($appointment->end_time, 0, 5))
            : $start->copy()->addMinutes((int) ($data['duration_minutes'] ?? 60));
        if ($end->lte($start)) {
            $end = $start->copy()->addHour();
        }

        $names = collect(\App\Models\AppointmentParticipant::describe($appointment->participants()->get()))
            ->map(fn ($p) => "{$p['name']} ({$p['label']})")->join(', ');

        try {
            $meeting = app(\App\Services\GoogleMeetService::class)->createMeeting(
                $account,
                $appointment->title,
                $start,
                $end,
                $names ? "Participantes: {$names}\nCriada pelo Fastbar." : 'Criada pelo Fastbar.'
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $appointment->forceFill([
            'meeting_url' => $meeting['url'],
            'google_event_id' => $meeting['event_id'],
            'end_time' => $appointment->end_time ?: $end->format('H:i'),
        ])->save();

        return response()->json(new AppointmentResource($appointment->fresh()), 200);
    }

    /** Guarda o link da sala do Meet (colado pela pessoa depois que o Meet abre). */
    public function updateMeetingUrl(Request $request, Appointment $appointment)
    {
        if (!$this->canSee($request, $appointment)) {
            return $this->deny();
        }
        $data = $request->validate([
            'meeting_url' => ['required', 'string', 'max:255', 'regex:/^https:\/\/meet\.google\.com\/[a-z0-9\-]+/i'],
        ], [
            'meeting_url.regex' => 'Cole o link do Google Meet (começa com https://meet.google.com/).',
        ]);
        $appointment->forceFill(['meeting_url' => $data['meeting_url']])->save();

        return response()->json(new AppointmentResource($appointment->fresh()), 200);
    }

    public function clientAppointments(Client $client)
    {
        return $this->participantAppointments('client', $client->id, 'client_id');
    }

    public function partnerAppointments(Partner $partner)
    {
        return $this->participantAppointments('partner', $partner->id, 'partner_id');
    }

    public function supplierAppointments(Supplier $supplier)
    {
        return $this->participantAppointments('supplier', $supplier->id, 'supplier_id');
    }

    public function collaboratorAppointments(Collaborator $collaborator)
    {
        return $this->participantAppointments('collaborator', $collaborator->id);
    }

    /** Reuniões em que a pessoa participou (lista de participantes ou o campo antigo). */
    private function participantAppointments(string $type, int $id, ?string $legacyColumn = null)
    {
        $appointments = Appointment::query()
            ->where(function ($query) use ($type, $id, $legacyColumn) {
                $query->whereHas('participants', fn ($q) => $q->where('participant_type', $type)->where('participant_id', $id));
                if ($legacyColumn) {
                    $query->orWhere($legacyColumn, $id);
                }
            })
            ->with(['users:id,name', 'event:id,name', 'creator:id,name', 'client:id,name', 'partner:id,name', 'supplier:id,corporate_name,fantasy_name', 'participants', 'recordings'])
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->limit(200)
            ->get();

        return response()->json(['data' => AppointmentResource::collection($appointments)], 200);
    }

    private function canSee(Request $request, Appointment $appointment): bool
    {
        $user = $request->user();
        return $appointment->created_by === $user->id
            || $user->isAdmin()
            || $appointment->users()->where('users.id', $user->id)->exists();
    }

    private function deny()
    {
        return response()->json(['message' => 'Você não tem acesso a esta reunião.'], 403);
    }
}
