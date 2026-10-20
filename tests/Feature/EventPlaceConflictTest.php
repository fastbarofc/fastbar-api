<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Http\Middleware\Admin;
use App\Models\Address;
use App\Models\City;
use App\Models\Event;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPlaceConflictTest extends TestCase
{
    use RefreshDatabase;

    private EventPlace $place;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');

        $this->place = $this->completePlace('Espaço Lua');
        $this->date = now()->addMonth()->toDateString();
    }

    public function test_event_cannot_be_confirmed_on_the_place_and_day_of_a_confirmed_event(): void
    {
        $this->createEvent('Contrato', 'Formatura');
        $event = $this->createEvent('Aberto');

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Fechado')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event_place_id' => 'Formatura (Contrato)']);
    }

    public function test_confirmed_event_cannot_move_to_a_taken_place_or_day(): void
    {
        $this->createEvent('Fechado', 'Formatura');
        $otherDay = $this->createEvent('Fechado', 'Casamento', ['date' => now()->addMonths(2)->toDateString()]);
        $otherPlace = $this->createEvent('Planejamento', 'Aniversário', ['event_place_id' => $this->completePlace('Espaço Sol')->id]);

        $this->putJson("/api/events/{$otherDay->id}", ['date' => $this->date])
            ->assertJsonValidationErrors(['event_place_id' => 'Formatura (Fechado)']);

        $this->putJson("/api/events/{$otherPlace->id}", ['event_place_id' => $this->place->id])
            ->assertJsonValidationErrors(['event_place_id' => 'Formatura (Fechado)']);
    }

    public function test_pre_reservations_share_the_day_but_cannot_take_a_confirmed_one(): void
    {
        $first = $this->createEvent('Pré-Reserva', 'Formatura');
        $second = $this->createEvent('Aberto', 'Casamento');

        $this->putJson("/api/events/{$second->id}", ['event_status_id' => $this->statusId('Pré-Reserva')])
            ->assertOk();

        $first->update(['event_status_id' => $this->statusId('Fechado')]);

        $this->putJson("/api/events/{$second->id}", ['name' => 'Casamento Ana'])
            ->assertOk();

        $this->putJson("/api/events/{$second->id}", ['event_status_id' => $this->statusId('Fechado')])
            ->assertJsonValidationErrors(['event_place_id' => 'Formatura (Fechado)']);

        $third = $this->createEvent('Aberto', 'Aniversário');

        $this->putJson("/api/events/{$third->id}", ['event_status_id' => $this->statusId('Pré-Reserva')])
            ->assertJsonValidationErrors(['event_place_id' => 'Formatura (Fechado)']);
    }

    public function test_other_places_and_lost_events_do_not_conflict(): void
    {
        $this->createEvent('Fechado', 'Formatura', ['event_place_id' => $this->completePlace('Espaço Sol')->id]);
        $this->createEvent('Cancelado', 'Casamento');
        $this->createEvent('Fechou com o concorrente', 'Bodas');
        $event = $this->createEvent('Aberto');

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Fechado')])
            ->assertOk();
    }

    public function test_old_conflict_does_not_block_moving_forward_in_the_flow(): void
    {
        $this->createEvent('Fechado', 'Formatura');
        $event = $this->createEvent('Fechado', 'Casamento');

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Planejamento')])
            ->assertOk();
    }

    public function test_new_event_cannot_be_created_confirmed_on_a_taken_day(): void
    {
        $this->createEvent('Planejamento', 'Formatura');

        $this->postJson('/api/events', [
            'name' => 'Casamento',
            'event_status_id' => $this->statusId('Fechado'),
            'event_place_id' => $this->place->id,
            'date' => $this->date,
        ])->assertJsonValidationErrors(['event_place_id' => 'Formatura (Planejamento)']);
    }

    private function createEvent(string $status, string $name = 'Evento', array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => $name,
            'event_status_id' => $this->statusId($status),
            'event_place_id' => $this->place->id,
            'date' => $this->date,
        ]);
    }

    private function statusId(string $name): int
    {
        return EventStatus::where('name', $name)->value('id');
    }

    private function completePlace(string $name): EventPlace
    {
        $place = EventPlace::create(['name' => $name, 'responsible' => 'Ana', 'phone_2' => '1133334444']);
        $city = City::first();

        Address::create([
            'addressable_id' => $place->id,
            'addressable_type' => EventPlace::class,
            'type' => AddressTypeEnum::MAIN->value,
            'street' => 'Rua A',
            'number' => '10',
            'district' => 'Centro',
            'zip_code' => '01001-000',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
        ]);

        return $place;
    }
}
