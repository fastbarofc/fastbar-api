<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusChangesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_pandemia_status_is_no_longer_offered(): void
    {
        $this->assertFalse(EventStatus::where('name', 'Pandemia')->exists());

        $statuses = collect($this->getJson('/api/events/form-data')->json('event_statuses'))->pluck('name');

        $this->assertNotContains('Pandemia', $statuses);
        $this->assertContains('Contrato', $statuses);
    }

    public function test_pandemia_events_become_discarded(): void
    {
        $migration = require database_path('migrations/2026_10_15_000001_remove_pandemia_event_status.php');
        $migration->down();
        $active = $this->createEvent('Pandemia');
        $deleted = $this->createEvent('Pandemia');
        $deleted->delete();

        $migration->up();

        $this->assertSame($this->statusId('Descartado'), $active->fresh()->event_status_id);
        $this->assertSame($this->statusId('Descartado'), Event::withTrashed()->find($deleted->id)->event_status_id);
        $this->assertSoftDeleted('event_statuses', ['name' => 'Pandemia']);
    }

    public function test_calendar_hides_only_lost_statuses(): void
    {
        $statuses = ['Aberto', 'Pré-Reserva', 'Contrato', 'Fechado', 'Planejamento', 'Concluído', 'Descartado', 'Cancelado', 'Fechou com o concorrente'];

        foreach ($statuses as $status) {
            $this->createEvent($status, ['name' => $status, 'date' => '2026-10-20']);
        }

        $shown = collect($this->getJson('/api/events/calendar?start=2026-10-18&end=2026-10-24')->json('data'))->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Aberto', 'Pré-Reserva', 'Contrato', 'Fechado', 'Planejamento', 'Concluído'], $shown);
    }

    public function test_cancelling_or_discarding_requires_a_reason(): void
    {
        foreach (['Cancelado', 'Descartado'] as $status) {
            $event = $this->createEvent('Pré-Reserva');

            $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId($status)])
                ->assertJsonValidationErrors(['status_reason' => "Informe o motivo para mudar o evento para {$status}."]);

            $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId($status), 'status_reason' => 'Cliente desistiu'])
                ->assertOk()
                ->assertJsonPath('status_reason', 'Cliente desistiu');
        }
    }

    public function test_cancelled_event_saves_again_without_asking_the_reason(): void
    {
        $event = $this->createEvent('Cancelado', ['status_reason' => 'Cliente desistiu']);

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Cancelado'), 'name' => 'Outro nome'])
            ->assertOk();

        $this->assertSame('Cliente desistiu', $event->fresh()->status_reason);
    }

    public function test_reopening_the_event_clears_the_reason(): void
    {
        $event = $this->createEvent('Cancelado', ['status_reason' => 'Cliente desistiu']);

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Pré-Reserva')])->assertOk();

        $this->assertNull($event->fresh()->status_reason);
    }

    public function test_event_cannot_be_created_cancelled_without_reason(): void
    {
        $this->postJson('/api/events', ['name' => 'Casamento', 'event_status_id' => $this->statusId('Descartado')])
            ->assertJsonValidationErrors('status_reason');
    }

    public function test_saved_event_returns_its_new_status(): void
    {
        $event = $this->createEvent('Pré-Reserva');

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Descartado'), 'status_reason' => 'Sem retorno'])
            ->assertJsonPath('status.name', 'Descartado');
    }

    private function createEvent(string $status, array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Casamento',
            'event_status_id' => EventStatus::withTrashed()->where('name', $status)->value('id'),
        ]);
    }

    private function statusId(string $name): int
    {
        return EventStatus::where('name', $name)->value('id');
    }
}
