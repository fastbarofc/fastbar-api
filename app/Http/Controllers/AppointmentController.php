<?php

namespace App\Http\Controllers;

use App\Enums\NotificationTypeEnum;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /** Compromissos do usuário (criados por ele ou em que participa) entre from e to. */
    public function index(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $appointments = Appointment::query()
            ->involving($request->user()->id)
            ->whereDate('date', '>=', $request->input('from'))
            ->whereDate('date', '<=', $request->input('to'))
            ->with(['users:id,name', 'event:id,name', 'creator:id,name'])
            ->orderBy('date')
            ->orderByRaw('start_time IS NULL')
            ->orderBy('start_time')
            ->limit(2000)
            ->get();

        return response()->json(['data' => AppointmentResource::collection($appointments)], 200);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $data = $request->validated();
        $userIds = $data['user_ids'] ?? [];
        unset($data['user_ids']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $appointment = DB::transaction(function () use ($data, $userIds) {
            $appointment = Appointment::create($data);
            $appointment->users()->sync($userIds);
            return $appointment;
        });

        $this->notifyUsers($appointment, $userIds, $request->user());

        return response()->json(new AppointmentResource($appointment->fresh()), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if (!$this->canSee($request->user(), $appointment)) {
            return $this->deny();
        }

        return response()->json(new AppointmentResource($appointment), 200);
    }

    public function update(StoreAppointmentRequest $request, Appointment $appointment)
    {
        if (!$this->canEdit($request->user(), $appointment)) {
            return $this->deny();
        }

        $data = $request->validated();
        $newUserIds = array_key_exists('user_ids', $data) ? ($data['user_ids'] ?? []) : null;
        unset($data['user_ids']);
        $data['updated_by'] = $request->user()->id;

        $added = [];
        DB::transaction(function () use ($appointment, $data, $newUserIds, &$added) {
            $appointment->update($data);
            if ($newUserIds !== null) {
                $added = $appointment->users()->sync($newUserIds)['attached'];
            }
        });

        $this->notifyUsers($appointment, $added, $request->user());

        return response()->json(new AppointmentResource($appointment->fresh()), 200);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        if (!$this->canEdit($request->user(), $appointment)) {
            return $this->deny();
        }

        $appointment->delete();

        return response()->json([], 204);
    }

    private function canSee(User $user, Appointment $appointment): bool
    {
        return $this->canEdit($user, $appointment) || $appointment->users()->where('users.id', $user->id)->exists();
    }

    /** Só quem criou (ou Admin) altera ou exclui. */
    private function canEdit(User $user, Appointment $appointment): bool
    {
        return $appointment->created_by === $user->id || $user->isAdmin();
    }

    private function deny()
    {
        return response()->json(['message' => 'Só quem criou o compromisso pode alterar ou excluir.'], 403);
    }

    /** Avisa os participantes novos, menos quem fez a ação. */
    private function notifyUsers(Appointment $appointment, array $userIds, User $actor): void
    {
        $when = $appointment->date->format('d/m/Y');
        if ($appointment->start_time) {
            $when .= ' às ' . substr($appointment->start_time, 0, 5);
        }
        $message = "{$actor->name} incluiu você no compromisso \"{$appointment->title}\" em {$when}.";

        foreach (User::whereIn('id', $userIds)->where('id', '!=', $actor->id)->get() as $user) {
            try {
                $this->notificationService->create(
                    NotificationTypeEnum::APPOINTMENT_INVITED,
                    $user,
                    'Novo compromisso',
                    $message,
                    ['appointment_id' => $appointment->id, 'link' => '/admin/tasks'],
                    $appointment,
                );
            } catch (\Throwable $e) {
                // O compromisso já foi salvo; uma falha no aviso não deve desfazer isso.
                Log::warning('Falha ao notificar participante do compromisso', ['appointment_id' => $appointment->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
