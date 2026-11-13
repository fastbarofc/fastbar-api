<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventKit;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventShowHasKitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_show_tells_when_the_event_has_kits(): void
    {
        $event = $this->createEvent();
        EventKit::create(['event_id' => $event->id, 'name' => 'Kit Clássico']);

        $this->getJson("/api/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('has_kit', true);
    }

    public function test_show_tells_when_the_event_has_no_kits(): void
    {
        $event = $this->createEvent();

        $this->getJson("/api/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('has_kit', false);
    }

    private function createEvent(): Event
    {
        return Event::create([
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Pré-Reserva'])->id,
        ])->refresh();
    }
}
