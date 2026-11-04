<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Http\Middleware\Admin;
use App\Http\Resources\EventCalendarResource;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusRegistrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_client_partner_and_place_can_be_created_with_only_the_name_from_the_event(): void
    {
        $this->postJson('/api/clients', ['name' => 'Maria', 'type' => 'pj', 'quick_registration' => true, 'address' => $this->blankAddress()])
            ->assertCreated();
        $this->postJson('/api/partners', ['name' => 'Buffet Sol', 'quick_registration' => true])->assertCreated();
        $this->postJson('/api/event-places', ['name' => 'Espaço Lua', 'quick_registration' => true, 'address' => $this->blankAddress()])
            ->assertCreated();
    }

    public function test_calendar_shows_place_whose_address_has_no_city_yet(): void
    {
        $place = EventPlace::create(['name' => 'Espaço Lua']);
        Address::create(['addressable_id' => $place->id, 'addressable_type' => EventPlace::class, 'type' => 'main']);
        $event = $this->createEvent('Pré-Reserva', ['event_place_id' => $place->id, 'date' => '2026-12-01']);

        $this->assertSame('Espaço Lua', (new EventCalendarResource($event))->toArray(request())['event_place']);
    }

    public function test_quick_registration_still_requires_the_name(): void
    {
        $this->postJson('/api/clients', ['quick_registration' => true])->assertJsonValidationErrors('name');
        $this->postJson('/api/partners', ['quick_registration' => true])->assertJsonValidationErrors('name');
        $this->postJson('/api/event-places', ['quick_registration' => true])->assertJsonValidationErrors('name');
    }

    public function test_full_registration_keeps_requiring_the_other_fields(): void
    {
        $this->postJson('/api/clients', ['name' => 'Maria', 'type' => 'pf'])->assertJsonValidationErrors('phone_1');
        $this->postJson('/api/partners', ['name' => 'Buffet Sol'])->assertJsonValidationErrors('phone_1');
        $this->postJson('/api/event-places', ['name' => 'Espaço Lua', 'address' => $this->blankAddress()])
            ->assertJsonValidationErrors('street');
    }

    public function test_status_cannot_advance_while_registrations_are_incomplete(): void
    {
        $event = $this->createEvent('Pré-Reserva', [
            'client_id' => Client::create(['name' => 'Maria', 'type' => 'pj'])->id,
            'partner_id' => Partner::create(['name' => 'Buffet Sol'])->id,
            'event_place_id' => EventPlace::create(['name' => 'Espaço Lua'])->id,
        ]);

        $message = $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Fechado')])
            ->assertJsonValidationErrors('event_status_id')
            ->json('message');

        $this->assertStringContainsString('O cliente "Maria" está com cadastro incompleto (telefone, endereço, dados do representante legal). Preencha na página de Clientes.', $message);
        $this->assertStringContainsString('O parceiro "Buffet Sol" está com cadastro incompleto (telefone). Preencha na página de Parceiros.', $message);
        $this->assertStringContainsString('O local "Espaço Lua" está com cadastro incompleto (responsável, telefone, endereço). Preencha na página de Locais.', $message);
        $this->assertSame($this->statusId('Pré-Reserva'), $event->fresh()->event_status_id);
    }

    public function test_status_advances_when_registrations_are_complete(): void
    {
        $event = $this->createEvent('Pré-Reserva', [
            'client_id' => $this->completeClient()->id,
            'partner_id' => Partner::create(['name' => 'Buffet Sol', 'phone_1' => '11999999999'])->id,
            'event_place_id' => $this->completePlace()->id,
        ]);

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Fechado')])
            ->assertOk();

        $this->assertSame($this->statusId('Fechado'), $event->fresh()->event_status_id);
    }

    public function test_only_the_incomplete_registration_is_reported(): void
    {
        $event = $this->createEvent('Pré-Reserva', [
            'client_id' => $this->completeClient()->id,
            'partner_id' => Partner::create(['name' => 'Buffet Sol'])->id,
        ]);

        $message = $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Fechado')])
            ->assertJsonValidationErrors('event_status_id')
            ->json('message');

        $this->assertStringContainsString('O parceiro "Buffet Sol"', $message);
        $this->assertStringNotContainsString('O cliente', $message);
        $this->assertStringNotContainsString('O local', $message);
    }

    public function test_lost_statuses_do_not_require_complete_registrations(): void
    {
        $event = $this->createEvent('Pré-Reserva', ['client_id' => Client::create(['name' => 'Maria'])->id]);

        $this->putJson("/api/events/{$event->id}", ['event_status_id' => $this->statusId('Descartado'), 'status_reason' => 'Sem retorno'])
            ->assertOk();
    }

    public function test_event_already_advanced_keeps_saving_without_changing_status_or_registrations(): void
    {
        $client = Client::create(['name' => 'Maria']);
        $event = $this->createEvent('Fechado', ['client_id' => $client->id]);

        $this->putJson("/api/events/{$event->id}", [
            'event_status_id' => $this->statusId('Fechado'),
            'client_id' => $client->id,
            'name' => 'Casamento da Maria',
        ])->assertJsonMissingValidationErrors('event_status_id');
    }

    public function test_advanced_event_cannot_switch_to_an_incomplete_registration(): void
    {
        $event = $this->createEvent('Fechado', ['client_id' => $this->completeClient()->id]);

        $this->putJson("/api/events/{$event->id}", [
            'event_status_id' => $this->statusId('Fechado'),
            'client_id' => Client::create(['name' => 'Maria'])->id,
        ])->assertJsonValidationErrors('event_status_id');
    }

    public function test_event_cannot_be_created_already_advanced_with_incomplete_registrations(): void
    {
        $this->postJson('/api/events', [
            'event_status_id' => $this->statusId('Contrato'),
            'event_place_id' => EventPlace::create(['name' => 'Espaço Lua'])->id,
        ])->assertJsonValidationErrors('event_status_id');
    }

    private function createEvent(string $status, array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Casamento',
            'event_status_id' => $this->statusId($status),
        ]);
    }

    private function statusId(string $name): int
    {
        return EventStatus::where('name', $name)->value('id');
    }

    private function completeClient(): Client
    {
        $client = Client::create([
            'name' => 'Empresa Ltda',
            'type' => 'pj',
            'phone_1' => '11999999999',
        ]);

        $this->createAddress($client, AddressTypeEnum::MAIN);
        $this->createAddress($client->representatives()->create(['name' => 'João', 'cpf' => '52998224725']), AddressTypeEnum::REPRESENTATIVE);

        return $client;
    }

    private function completePlace(): EventPlace
    {
        $place = EventPlace::create(['name' => 'Espaço Lua', 'responsible' => 'Ana', 'phone_2' => '1133334444']);
        $this->createAddress($place, AddressTypeEnum::MAIN);

        return $place;
    }

    private function createAddress($addressable, AddressTypeEnum $type): void
    {
        $city = City::first();

        Address::create([
            'addressable_id' => $addressable->id,
            'addressable_type' => $addressable::class,
            'type' => $type->value,
            'street' => 'Rua A',
            'number' => '10',
            'district' => 'Centro',
            'zip_code' => '01001-000',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
        ]);
    }

    private function blankAddress(): array
    {
        return array_fill_keys(['street', 'district', 'complement', 'city_id', 'state_id', 'number', 'zip_code', 'reference'], '');
    }
}
