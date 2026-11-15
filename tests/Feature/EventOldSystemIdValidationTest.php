<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOldSystemIdValidationTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'O ID do sistema antigo deve conter apenas números.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_update_rejects_letters_in_the_old_system_id(): void
    {
        $event = $this->createEvent(['old_system_id' => 123]);

        $this->putJson("/api/events/{$event->id}", ['old_system_id' => '12a'])
            ->assertJsonValidationErrors(['old_system_id' => self::MESSAGE]);

        $this->assertSame(123, $event->fresh()->old_system_id);
    }

    public function test_store_rejects_letters_in_the_old_system_id(): void
    {
        $this->postJson('/api/events', ['name' => 'Casamento', 'old_system_id' => 'abc'])
            ->assertJsonValidationErrors(['old_system_id' => self::MESSAGE]);
    }

    public function test_update_saves_a_numeric_old_system_id(): void
    {
        $event = $this->createEvent();

        $this->putJson("/api/events/{$event->id}", ['old_system_id' => '4567'])->assertOk();

        $this->assertSame(4567, $event->fresh()->old_system_id);
    }

    public function test_update_clears_the_old_system_id(): void
    {
        $event = $this->createEvent(['old_system_id' => 123]);

        $this->putJson("/api/events/{$event->id}", ['old_system_id' => ''])->assertOk();

        $this->assertNull($event->fresh()->old_system_id);
    }

    private function createEvent(array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Pré-Reserva'])->id,
        ])->refresh();
    }
}
