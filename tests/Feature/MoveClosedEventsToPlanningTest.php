<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventHistory;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\Sector;
use App\Models\SectorOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveClosedEventsToPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Collaborator $collaborator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->collaborator = $this->collaborator($this->user);
    }

    public function test_closed_event_within_30_days_moves_to_planning_and_notifies_the_responsible(): void
    {
        $event = $this->readyEvent(['name' => 'Casamento da Ana', 'date' => now()->addDays(20)->toDateString()]);

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();

        $this->assertSame($this->statusId('Planejamento'), $event->fresh()->event_status_id);

        $notification = Notification::where('user_id', $this->user->id)->sole();
        $this->assertSame(NotificationTypeEnum::EVENT_MOVED_TO_PLANNING->value, $notification->type->name);
        $this->assertSame($event->id, $notification->sourceable_id);
        $this->assertStringContainsString('Casamento da Ana', $notification->message);
        $this->assertStringContainsString('alterado automaticamente de Fechado para Planejamento porque faltam 20 dias para o evento.', $notification->message);

        $history = EventHistory::where('event_id', $event->id)->sole();
        $this->assertNull($history->user_id);
        $this->assertEquals(
            [['field' => 'event_status_id', 'label' => 'Status do Evento', 'old' => 'Fechado', 'new' => 'Planejamento']],
            $history->changes
        );
    }

    public function test_every_closed_event_30_days_or_less_away_is_moved_even_if_the_date_has_passed(): void
    {
        $moved = [
            $this->readyEvent(['date' => now()->addDays(30)->toDateString()]),
            $this->readyEvent(['date' => now()->toDateString()]),
            $this->readyEvent(['date' => now()->subMonths(3)->toDateString()]),
        ];

        $kept = [
            $this->readyEvent(['date' => now()->addDays(31)->toDateString()]),
            $this->readyEvent(['date' => now()->addDays(10)->toDateString(), 'event_status_id' => $this->statusId('Contrato')]),
            $this->readyEvent(['date' => now()->addDays(10)->toDateString(), 'is_draft' => true]),
        ];

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();

        foreach ($moved as $event) {
            $this->assertSame($this->statusId('Planejamento'), $event->fresh()->event_status_id);
        }

        foreach ($kept as $event) {
            $this->assertSame($event->event_status_id, $event->fresh()->event_status_id);
        }

        $this->assertSame(3, Notification::count());
    }

    public function test_moved_notice_says_how_many_days_are_left(): void
    {
        $phrases = [
            1 => 'porque falta 1 dia para o evento.',
            0 => 'porque o evento é hoje.',
            -1 => 'porque o evento foi ontem.',
            -5 => 'porque o evento foi há 5 dias.',
        ];

        foreach (array_keys($phrases) as $days) {
            $this->readyEvent(['name' => "Evento {$days}", 'date' => now()->addDays($days)->toDateString()]);
        }

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();

        foreach ($phrases as $days => $phrase) {
            $this->assertStringContainsString($phrase, Notification::where('data->event_name', "Evento {$days}")->sole()->message);
        }
    }

    public function test_event_with_pending_data_stays_closed_and_the_responsible_is_told_why(): void
    {
        $event = $this->readyEvent([
            'name' => 'Formatura',
            'time' => null,
            'event_guests' => null,
            'partner_id' => Partner::create(['name' => 'Buffet Sol'])->id,
        ]);

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();

        $this->assertSame($this->statusId('Fechado'), $event->fresh()->event_status_id);

        $notification = Notification::where('user_id', $this->user->id)->sole();
        $this->assertSame(NotificationTypeEnum::EVENT_PLANNING_BLOCKED->value, $notification->type->name);
        $this->assertStringContainsString('Formatura', $notification->message);
        $this->assertStringContainsString('deveria ir para Planejamento porque faltam 15 dias para o evento, mas não foi possível alterar o status automaticamente.', $notification->message);
        $this->assertSame(
            [
                'Preencha no evento: horário, convidados.',
                'O parceiro "Buffet Sol" está com cadastro incompleto (telefone). Preencha na página de Parceiros.',
            ],
            $notification->data['pending']
        );
    }

    public function test_event_without_client_partner_or_place_is_not_moved(): void
    {
        $event = $this->readyEvent(['client_id' => null, 'partner_id' => null, 'event_place_id' => null]);

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();

        $this->assertSame($this->statusId('Fechado'), $event->fresh()->event_status_id);
        $this->assertSame(
            ['Preencha no evento: cliente, parceiro, local.'],
            Notification::sole()->data['pending']
        );
    }

    public function test_blocked_notice_is_not_repeated_while_the_previous_one_is_unread(): void
    {
        $event = $this->readyEvent(['time' => null]);

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();
        $this->travel(1)->days();
        $this->artisan('events:move-closed-to-planning')->assertSuccessful();
        $this->assertSame(1, Notification::count());

        Notification::query()->update(['read_at' => now()]);
        $this->artisan('events:move-closed-to-planning')->assertSuccessful();
        $this->assertSame(2, Notification::count());

        $event->update(['time' => '20:00', 'min_guests' => null]);
        $this->artisan('events:move-closed-to-planning')->assertSuccessful();
        $this->assertSame(3, Notification::count());
    }

    public function test_event_without_responsible_is_still_moved(): void
    {
        $event = $this->readyEvent(['collaborator_id' => null]);

        $this->artisan('events:move-closed-to-planning')->assertSuccessful();

        $this->assertSame($this->statusId('Planejamento'), $event->fresh()->event_status_id);
        $this->assertSame(0, Notification::count());
    }

    private function readyEvent(array $attributes = []): Event
    {
        $client = $this->completeClient();

        return Event::create($attributes + [
            'name' => 'Casamento',
            'date' => now()->addDays(15)->toDateString(),
            'time' => '19:00',
            'is_draft' => false,
            'event_status_id' => $this->statusId('Fechado'),
            'collaborator_id' => $this->collaborator->id,
            'client_id' => $client->id,
            'invoice_client_id' => $client->id,
            'partner_id' => Partner::create(['name' => 'Buffet Lua', 'phone_1' => '11999999999'])->id,
            'event_place_id' => $this->completePlace()->id,
            'event_type_id' => EventType::create(['name' => 'Casamento'])->id,
            'event_guests' => 150,
            'min_guests' => 100,
        ]);
    }

    private function statusId(string $name): int
    {
        return EventStatus::where('name', $name)->value('id');
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

    private function completeClient(): Client
    {
        $client = Client::create(['name' => 'Maria', 'type' => 'pf', 'phone_1' => '11999999999']);
        $this->createAddress($client, AddressTypeEnum::MAIN);

        return $client;
    }

    private function completePlace(): EventPlace
    {
        $place = EventPlace::create(['name' => 'Espaço Lua', 'responsible' => 'Ana', 'phone_1' => '1133334444']);
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
}
