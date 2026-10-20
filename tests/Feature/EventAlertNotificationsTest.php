<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\Notification;
use App\Models\Sector;
use App\Models\SectorOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAlertNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_45_day_alert_is_not_sent_for_lost_events(): void
    {
        $user = User::factory()->create();
        $collaborator = $this->collaborator($user);

        foreach (['Fechado', 'Pré-Reserva', 'Cancelado', 'Descartado', 'Fechou com o concorrente'] as $status) {
            Event::create([
                'name' => $status,
                'date' => now()->addDays(45)->toDateString(),
                'is_draft' => false,
                'collaborator_id' => $collaborator->id,
                'event_status_id' => EventStatus::where('name', $status)->value('id'),
            ]);
        }

        $this->artisan('notifications:send-event-alerts')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            ['Fechado', 'Pré-Reserva'],
            Notification::where('user_id', $user->id)->get()->pluck('data.event_name')->all()
        );
    }

    private function collaborator(User $user): Collaborator
    {
        $office = SectorOffice::firstOrCreate(
            ['name' => 'Vendedor'],
            ['sector_id' => Sector::firstOrCreate(['name' => 'Comercial'])->id]
        );

        return Collaborator::create([
            'name' => 'Carla',
            'email' => 'carla@fastbar.test',
            'phone_1' => '41999999999',
            'sector_id' => $office->sector_id,
            'sector_office_id' => $office->id,
            'user_id' => $user->id,
        ]);
    }
}
