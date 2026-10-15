<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\BlockedDate;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedDateEventConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_event_cannot_be_created_on_a_blocked_date(): void
    {
        BlockedDate::create(['date' => '2027-03-10', 'description' => 'Reforma do salão']);

        $this->postJson('/api/events', ['name' => 'Casamento', 'date' => '2027-03-10'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'O dia 10/03/2027 está bloqueado (Reforma do salão). Escolha outra data para o evento.');
    }

    public function test_event_date_cannot_be_moved_to_a_blocked_date(): void
    {
        BlockedDate::create(['date' => '2027-03-10', 'description' => 'Reforma do salão']);
        $event = $this->createEvent('Pré-Reserva', ['date' => '2027-03-09']);

        $this->putJson("/api/events/{$event->id}", ['date' => '2027-03-10'])
            ->assertJsonValidationErrors(['date' => 'O dia 10/03/2027 está bloqueado (Reforma do salão). Escolha outra data para o evento.']);

        $this->assertSame('2027-03-09', $event->fresh()->date->toDateString());
    }

    public function test_event_already_on_a_blocked_date_saves_while_keeping_the_date(): void
    {
        $event = $this->createEvent('Pré-Reserva', ['date' => '2027-03-10']);
        BlockedDate::create(['date' => '2027-03-10', 'description' => 'Reforma do salão']);

        $this->putJson("/api/events/{$event->id}", ['date' => '2027-03-10', 'name' => 'Outro nome'])->assertOk();
    }

    public function test_event_on_a_blocked_date_cannot_be_duplicated(): void
    {
        $event = $this->createEvent('Cancelado', ['date' => '2027-03-10', 'status_reason' => 'Cliente desistiu']);
        BlockedDate::create(['date' => '2027-03-10', 'description' => 'Reforma do salão']);

        $this->getJson("/api/events/{$event->id}/duplicate")
            ->assertJsonValidationErrors(['date' => 'O dia 10/03/2027 está bloqueado (Reforma do salão). Escolha outra data para o evento.']);

        $this->assertSame(1, Event::count());
    }

    public function test_date_with_an_event_cannot_be_blocked(): void
    {
        $this->createEvent('Fechado', ['name' => 'Casamento Ana', 'date' => '2027-03-10']);
        $this->createEvent('Aberto', ['name' => 'Formatura', 'date' => '2027-03-10']);

        $this->postJson('/api/blocked-dates', ['date' => '2027-03-10', 'description' => 'Reforma do salão'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Não é possível bloquear o dia 10/03/2027: já existe evento nele (Casamento Ana, Formatura).');

        $this->assertSame(0, BlockedDate::count());
    }

    public function test_lost_events_do_not_prevent_blocking_the_date(): void
    {
        foreach (EventStatus::LOST as $status) {
            $this->createEvent($status, ['date' => '2027-03-10', 'status_reason' => 'Cliente desistiu']);
        }

        $this->postJson('/api/blocked-dates', ['date' => '2027-03-10', 'description' => 'Reforma do salão'])->assertCreated();
    }

    public function test_blocked_date_cannot_be_moved_to_a_date_with_an_event(): void
    {
        $this->createEvent('Fechado', ['name' => 'Casamento Ana', 'date' => '2027-03-11']);
        $blockedDate = BlockedDate::create(['date' => '2027-03-10', 'description' => 'Reforma do salão']);

        $this->putJson("/api/blocked-dates/{$blockedDate->id}", ['date' => '2027-03-11'])
            ->assertJsonValidationErrors(['date' => 'Não é possível bloquear o dia 11/03/2027: já existe evento nele (Casamento Ana).']);
    }

    public function test_blocked_date_keeping_its_date_can_be_edited_even_with_an_event_on_it(): void
    {
        $this->createEvent('Fechado', ['date' => '2027-03-10']);
        $blockedDate = BlockedDate::create(['date' => '2027-03-10', 'description' => 'Reforma do salão']);

        $this->putJson("/api/blocked-dates/{$blockedDate->id}", ['date' => '2027-03-10', 'description' => 'Reforma da cozinha'])
            ->assertOk();
    }

    private function createEvent(string $status, array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Casamento',
            'event_status_id' => EventStatus::where('name', $status)->value('id'),
        ]);
    }
}
