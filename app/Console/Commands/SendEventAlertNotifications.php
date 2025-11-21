<?php

namespace App\Console\Commands;

use App\Enums\NotificationTypeEnum;
use App\Models\Event;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEventAlertNotifications extends Command
{
    protected $signature = 'notifications:send-event-alerts';

    protected $description = 'Envia notificações diárias para eventos que acontecerão em 45 dias.';

    public function __construct(private NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetDate = Carbon::now()->addDays(45)->startOfDay();

        $events = Event::query()
            ->whereDate('date', $targetDate)
            ->where('is_draft', false)
            ->with('collaborator.user')
            ->get();

        foreach ($events as $event) {
            $collaborator = $event->collaborator;
            $user = $collaborator?->user;

            if (!$user) {
                continue;
            }

            $messageDate = $event->date?->format('d/m/Y') ?? '';

            $this->notificationService->create(
                NotificationTypeEnum::EVENT_ALERT_45_DAYS,
                $user,
                'Lembrete de evento em 45 dias',
                "O evento {$event->name} está agendado para {$messageDate}. Acesse o evento para mais detalhes.",
                [
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'old_system_id' => $event->old_system_id,
                    'client_id' => $event->client_id,
                    'event_id' => $event->event_id,
                    'invoice_client_id' => $event->invoice_client_id,
                    'collaborator_id' => $event->collaborator_id,
                    'event_type_id' => $event->event_type_id,
                    'event_place_id' => $event->event_place_id,
                    'event_status_id' => $event->event_status_id,
                    'partner_id' => $event->partner_id,
                    'decorator_id' => $event->decorator_id,
                    'event_capture_id' => $event->event_capture_id,
                    'ceremonial_id' => $event->ceremonial_id,
                    'lease' => $event->lease,
                ],
                $event,
            );
        }

        return self::SUCCESS;
    }
}

