<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\MeetingSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Faz o resumo da reunião depois de responder ao navegador (pode levar alguns minutos). */
class SummarizeMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public function __construct(public int $appointmentId)
    {
    }

    public function handle(MeetingSummaryService $service): void
    {
        $appointment = Appointment::find($this->appointmentId);
        if (!$appointment) {
            return;
        }

        @set_time_limit(1200);

        try {
            $service->summarize($appointment);
        } catch (\RuntimeException $e) {
            $appointment->update(['summary_status' => 'error', 'summary_error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            $appointment->update(['summary_status' => 'error', 'summary_error' => 'A IA está indisponível agora. Tente de novo em alguns minutos.']);
        }
    }
}
