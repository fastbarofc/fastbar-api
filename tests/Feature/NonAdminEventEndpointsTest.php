<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonAdminEventEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_admin_role_opens_the_event_tabs(): void
    {
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
        $event = Event::create([
            'name' => 'Casamento',
            'event_status_id' => EventStatus::create(['name' => 'Pré-Reserva'])->id,
        ]);

        foreach (['office-events', 'gifts', 'materials', 'extra-sales', 'legal-representatives'] as $tab) {
            $this->getJson("/api/events/{$event->id}/{$tab}")->assertOk();
        }
    }
}
