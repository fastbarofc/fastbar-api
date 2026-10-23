<?php

namespace App\Console\Commands;

use App\Enums\NotificationTypeEnum;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\Notification;
use App\Services\EventHistoryService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MoveClosedEventsToPlanning extends Command
{
    private const DAYS_BEFORE_EVENT = 30;

    // Planejamento trava o formulário do evento, então o que faltar aqui não dá mais para corrigir depois.
    private const REQUIRED_FIELDS = [
        'client_id'         => 'cliente',
        'invoice_client_id' => 'cliente da nota fiscal',
        'partner_id'        => 'parceiro',
        'event_place_id'    => 'local',
        'event_type_id'     => 'tipo de evento',
        'time'              => 'horário',
        'event_guests'      => 'convidados',
        'min_guests'        => 'mínimo de convidados',
    ];

    protected $signature = 'events:move-closed-to-planning';

    protected $description = 'Move para Planejamento os eventos Fechados a 30 dias ou menos da data e avisa o responsável.';

    public function __construct(
        private NotificationService $notificationService,
        private EventHistoryService $historyService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $planning = EventStatus::where('name', 'Planejamento')->firstOrFail();

        $events = Event::query()
            ->where('is_draft', false)
            ->whereHas('eventStatus', fn ($query) => $query->where('name', 'Fechado'))
            ->whereDate('date', '<=', today()->addDays(self::DAYS_BEFORE_EVENT))
            ->with('collaborator.user')
            ->get();

        $moved = 0;

        foreach ($events as $event) {
            try {
                $pending = $this->pendingRequirements($event);

                if (empty($pending)) {
                    $this->moveToPlanning($event, $planning);
                    $moved++;
                } else {
                    $this->notifyBlocked($event, $pending);
                }
            } catch (Throwable $e) {
                Log::error('MoveClosedEventsToPlanning - falha ao processar evento', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("{$moved} de {$events->count()} evento(s) movido(s) para Planejamento.");

        return self::SUCCESS;
    }

    private function pendingRequirements(Event $event): array
    {
        $missingFields = collect(self::REQUIRED_FIELDS)
            ->filter(fn (string $label, string $field) => blank($event->{$field}))
            ->values();

        $pending = $missingFields->isEmpty() ? [] : ['Preencha no evento: ' . $missingFields->implode(', ') . '.'];

        $registrationIds = array_filter($event->only(array_keys(Event::REQUIRED_REGISTRATIONS)));

        return [...$pending, ...Event::incompleteRegistrationMessages($registrationIds)];
    }

    private function moveToPlanning(Event $event, EventStatus $planning): void
    {
        DB::transaction(function () use ($event, $planning) {
            $originalData = $event->getAttributes();

            $event->update(['event_status_id' => $planning->id]);

            $this->historyService->logEventUpdate($event, $originalData);
        });

        $this->notifyResponsible(
            $event,
            NotificationTypeEnum::EVENT_MOVED_TO_PLANNING,
            'Evento movido para Planejamento',
            "O status do evento {$event->name}, marcado para {$event->date->format('d/m/Y')}, foi alterado automaticamente de Fechado para Planejamento porque {$this->daysUntilEvent($event)}.",
        );
    }

    private function notifyBlocked(Event $event, array $pending): void
    {
        if ($this->hasUnreadBlockedNotice($event, $pending)) {
            return;
        }

        $message = "O evento {$event->name}, marcado para {$event->date->format('d/m/Y')}, deveria ir para Planejamento porque {$this->daysUntilEvent($event)}, mas não foi possível alterar o status automaticamente. "
            . implode(' ', $pending)
            . ' Com as pendências resolvidas, ele será movido na próxima verificação diária.';

        $this->notifyResponsible($event, NotificationTypeEnum::EVENT_PLANNING_BLOCKED, 'Evento não foi para Planejamento', $message, ['pending' => $pending]);
    }

    private function daysUntilEvent(Event $event): string
    {
        $days = today()->diffInDays($event->date, false);

        return match (true) {
            $days > 1 => "faltam {$days} dias para o evento",
            $days === 1 => 'falta 1 dia para o evento',
            $days === 0 => 'o evento é hoje',
            $days === -1 => 'o evento foi ontem',
            default => 'o evento foi há ' . abs($days) . ' dias',
        };
    }

    private function notifyResponsible(Event $event, NotificationTypeEnum $type, string $title, string $message, array $data = []): void
    {
        $user = $event->collaborator?->user;

        if (!$user) {
            Log::warning('MoveClosedEventsToPlanning - evento sem responsável com usuário para notificar', [
                'event_id' => $event->id,
                'notification_type' => $type->value,
            ]);

            return;
        }

        $this->notificationService->create(
            $type,
            $user,
            $title,
            $message,
            [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_date' => $event->date->toDateString(),
                'collaborator_id' => $event->collaborator_id,
                ...$data,
            ],
            $event,
        );
    }

    // O job roda todo dia e a contagem de dias muda a cada aviso: compara só as pendências para não repetir aviso não lido.
    private function hasUnreadBlockedNotice(Event $event, array $pending): bool
    {
        return Notification::query()
            ->unread()
            ->where('user_id', $event->collaborator?->user_id)
            ->where('sourceable_type', $event->getMorphClass())
            ->where('sourceable_id', $event->id)
            ->whereHas('type', fn ($query) => $query->where('name', NotificationTypeEnum::EVENT_PLANNING_BLOCKED->value))
            ->get()
            ->contains(fn (Notification $notification) => ($notification->data['pending'] ?? null) === $pending);
    }
}
