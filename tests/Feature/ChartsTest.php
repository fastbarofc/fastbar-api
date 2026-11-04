<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\CompetingCompany;
use App\Models\Event;
use App\Models\EventPayment;
use App\Models\EventPlace;
use App\Models\EventStatus;
use App\Models\EventStatusHistory;
use App\Models\LostReason;
use App\Models\ParthenonBankAccount;
use App\Models\PaymentMethod;
use App\Models\Sector;
use App\Models\SectorOffice;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChartsTest extends TestCase
{
    use RefreshDatabase;

    private EventPlace $curitibaPlace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs($this->userWithRole('Admin'), 'api');

        $parana = State::create(['name' => 'Paraná', 'letter' => 'PR', 'iso' => 41]);
        $curitiba = City::create(['state_id' => $parana->id, 'name' => 'Curitiba', 'iso' => 4106902, 'iso_ddd' => 41]);
        $this->curitibaPlace = $this->place('Espaço Batel', $curitiba);
    }

    public function test_financial_charts_use_confirmed_events_by_event_date(): void
    {
        $wedding = $this->event('Fechado', ['date' => '2026-01-20', 'total_value' => 10000, 'event_guests' => 150]);
        $this->pay($wedding, 4000, '2025-12-10');
        $this->event('Contrato', ['date' => '2026-03-05', 'total_value' => 6000, 'event_guests' => 250]);
        $this->event('Aberto', ['date' => '2026-02-10', 'total_value' => 5000, 'event_guests' => 100]);
        $this->event('Cancelado', ['date' => '2026-02-11', 'total_value' => 7000, 'event_guests' => 100]);
        $this->event('Fechado', ['date' => '2026-05-01', 'total_value' => 9000, 'event_guests' => 100]);

        $response = $this->getJson('/api/charts/financial?period_start=2026-01-01&period_end=2026-03-31')->assertOk();

        $response->assertJsonPath('summary.events', 2)
            ->assertJsonPath('summary.total_value', 16000)
            ->assertJsonPath('summary.paid', 4000)
            ->assertJsonPath('summary.balance', 12000)
            ->assertJsonPath('summary.value_per_guest', 40)
            ->assertJsonCount(3, 'by_month')
            ->assertJsonPath('by_month.0.month', '2026-01')
            ->assertJsonPath('by_month.0.paid', 4000)
            ->assertJsonPath('by_month.0.balance', 6000)
            ->assertJsonPath('by_month.1.month', '2026-02')
            ->assertJsonPath('by_month.1.total_value', 0)
            ->assertJsonPath('by_month.2.total_value', 6000)
            ->assertJsonPath('breakdowns.city.0.label', 'Curitiba - PR')
            ->assertJsonPath('breakdowns.city.0.total_value', 16000)
            ->assertJsonPath('payment_methods.0.label', 'PIX')
            ->assertJsonPath('payment_methods.0.share', 1)
            ->assertJsonPath('guest_bands.0.events', 0)
            ->assertJsonPath('guest_bands.1.events', 1)
            ->assertJsonPath('guest_bands.1.value_per_guest', 66.67)
            ->assertJsonPath('guest_bands.2.average_ticket', 6000);
    }

    public function test_financial_breakdown_folds_the_tail_into_others_and_skips_events_without_value(): void
    {
        foreach (range(1, 10) as $i) {
            $client = Client::create(['name' => "Cliente {$i}", 'type' => 'pf']);
            $this->event('Fechado', ['client_id' => $client->id, 'total_value' => $i * 1000]);
        }

        $withoutValue = Client::create(['name' => 'Sem valor', 'type' => 'pf']);
        $this->event('Fechado', ['client_id' => $withoutValue->id]);

        $clients = $this->getJson('/api/charts/financial')->assertOk()->json('breakdowns.client');

        $this->assertCount(9, $clients);
        $this->assertNotContains('Sem valor', array_column($clients, 'label'));
        $this->assertSame('Cliente 10', $clients[0]['label']);
        $this->assertSame('Outros (2)', $clients[8]['label']);
        $this->assertEquals(3000, $clients[8]['total_value']);
        $this->assertEquals(round(3000 / 55000, 4), $clients[8]['share']);
        $this->assertNull($clients[8]['average_ticket']);
    }

    public function test_funnel_counts_lost_events_up_to_the_stage_they_reached(): void
    {
        $seller = $this->collaborator('Carla');

        foreach (['Aberto', 'Pré-Reserva', 'Contrato', 'Fechado', 'Concluído'] as $status) {
            $this->event($status);
        }

        $lost = $this->event('Cancelado');
        EventStatusHistory::create(['event_id' => $lost->id, 'event_status_id' => $this->statusId('Contrato'), 'collaborator_id' => $seller->id]);

        $funnel = $this->getJson('/api/charts/commercial')->assertOk()->json('funnel');

        $this->assertSame(
            ['Oportunidades' => 6, 'Pré-Reserva' => 5, 'Contrato' => 4, 'Fechado' => 2],
            collect($funnel)->pluck('events', 'label')->all()
        );
    }

    public function test_commercial_charts_break_down_results_losses_and_lead_time(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');

        $carla = $this->collaborator('Carla');
        $price = LostReason::create(['name' => 'Preço']);
        $rival = CompetingCompany::create(['name' => 'Bar Rival']);

        $this->event('Fechado', ['collaborator_id' => $carla->id, 'date' => '2026-06-11', 'total_value' => 1000]);
        $this->event('Contrato', ['collaborator_id' => $carla->id, 'date' => '2026-09-09', 'total_value' => 3000]);
        $this->event('Fechado', ['collaborator_id' => $carla->id, 'date' => '2026-06-05', 'old_system_id' => 99]);
        $this->event('Cancelado', ['collaborator_id' => $carla->id, 'lost_reason_id' => $price->id]);
        $this->event('Fechou com o concorrente', ['lost_reason_id' => $price->id, 'competing_company_id' => $rival->id]);

        $response = $this->getJson('/api/charts/commercial')->assertOk();

        $response->assertJsonPath('summary.total', 5)
            ->assertJsonPath('summary.confirmed', 3)
            ->assertJsonPath('summary.lost', 2)
            ->assertJsonPath('summary.average_lead_days', 55)
            ->assertJsonPath('breakdowns.collaborator.0.label', 'Carla')
            ->assertJsonPath('breakdowns.collaborator.0.confirmed', 3)
            ->assertJsonPath('breakdowns.collaborator.0.conversion_rate', 0.75)
            ->assertJsonPath('lost_reasons.0.label', 'Preço')
            ->assertJsonPath('lost_reasons.0.events', 2)
            ->assertJsonCount(1, 'competitors')
            ->assertJsonPath('competitors.0.label', 'Bar Rival')
            ->assertJsonPath('lead_time.0.events', 1)
            ->assertJsonPath('lead_time.2.label', '3 a 6 meses')
            ->assertJsonPath('lead_time.2.events', 1);
    }

    public function test_commercial_period_follows_the_chosen_date(): void
    {
        Carbon::setTestNow('2026-03-10 09:00:00');
        $this->event('Fechado', ['date' => '2027-02-01']);

        $period = 'period_start=2026-01-01&period_end=2026-12-31';

        $this->getJson("/api/charts/commercial?{$period}")->assertJsonPath('summary.total', 0);
        $this->getJson("/api/charts/commercial?{$period}&date_field=created_at")->assertJsonPath('summary.total', 1);
    }

    public function test_monthly_charts_compare_with_the_previous_year(): void
    {
        $march = $this->event('Fechado', ['date' => '2026-03-07', 'total_value' => 8000, 'event_guests' => 200]);
        $this->event('Concluído', ['date' => '2026-03-14', 'total_value' => 2000, 'event_guests' => 100]);
        $this->event('Fechado', ['date' => '2025-03-08', 'total_value' => 5000]);
        $this->event('Cancelado', ['date' => '2026-03-20']);
        $this->event('Aberto', ['date' => '2026-03-21']);
        $this->pay($march, 3000, '2026-03-01');
        $this->pay($march, 1000, '2025-03-15');

        $response = $this->getJson('/api/charts/monthly?year=2026')->assertOk();

        $response->assertJsonPath('year', 2026)
            ->assertJsonCount(12, 'months')
            ->assertJsonPath('months.2.month', '2026-03')
            ->assertJsonPath('months.2.events', 2)
            ->assertJsonPath('months.2.guests', 300)
            ->assertJsonPath('months.2.previous_events', 1)
            ->assertJsonPath('months.2.total_value', 10000)
            ->assertJsonPath('months.2.previous_total_value', 5000)
            ->assertJsonPath('months.2.received', 3000)
            ->assertJsonPath('months.2.previous_received', 1000)
            ->assertJsonPath('months.2.opportunities', 4)
            ->assertJsonPath('months.2.negotiating', 1)
            ->assertJsonPath('months.2.lost', 1)
            ->assertJsonPath('months.2.conversion_rate', 0.6667)
            ->assertJsonPath('months.0.events', 0)
            ->assertJsonPath('weekdays.5.events', 2)
            ->assertJsonPath('heatmap.0.year', 2025)
            ->assertJsonPath('heatmap.1.events.2', 2)
            ->assertJsonPath('heatmap.1.total_value.2', 10000);

        $this->assertContains(2025, $response->json('years'));
        $this->assertContains((int) now()->year, $response->json('years'));
    }

    public function test_monthly_charts_only_show_what_the_user_can_see(): void
    {
        $this->event('Fechado', ['date' => '2026-03-07', 'total_value' => 8000]);

        $this->actingAs($this->userWithPermissions(['graficos-comercial']), 'api');
        $month = $this->getJson('/api/charts/monthly?year=2026')->assertOk()->json('months.2');
        $this->assertArrayNotHasKey('total_value', $month);
        $this->assertArrayHasKey('opportunities', $month);
        $this->assertArrayNotHasKey('total_value', $this->getJson('/api/charts/monthly?year=2026')->json('heatmap.0'));

        $this->actingAs($this->userWithPermissions(['graficos-financeiro']), 'api');
        $month = $this->getJson('/api/charts/monthly?year=2026')->assertOk()->json('months.2');
        $this->assertSame(8000, $month['total_value']);
        $this->assertArrayNotHasKey('opportunities', $month);

        $this->actingAs($this->userWithPermissions(['relatorios-financeiro']), 'api');
        $this->getJson('/api/charts/monthly')->assertForbidden();
    }

    public function test_charts_require_their_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['graficos-comercial']), 'api');

        $this->getJson('/api/charts/commercial')->assertOk();
        $this->getJson('/api/charts/financial')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['graficos-financeiro']), 'api');

        $this->getJson('/api/charts/financial')->assertOk();
        $this->getJson('/api/charts/commercial')->assertForbidden();
    }

    public function test_permissions_migration_replaces_the_old_charts_permission_and_grants_admin(): void
    {
        $this->assertFalse(Permission::where('slug', 'graficos-visualizar')->exists());

        $admin = Role::where('name', 'Admin')->first()->permissions;
        $this->assertTrue($admin->contains('slug', 'graficos-financeiro'));
        $this->assertTrue($admin->contains('slug', 'graficos-comercial'));
    }

    private function event(string $status, array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Evento',
            'is_draft' => false,
            'date' => '2026-06-15',
            'event_place_id' => $this->curitibaPlace->id,
            'event_status_id' => $this->statusId($status),
        ]);
    }

    private function pay(Event $event, float $value, string $date): void
    {
        EventPayment::create([
            'event_id' => $event->id,
            'value' => $value,
            'date' => $date,
            'payment_method_id' => PaymentMethod::firstOrCreate(['name' => 'PIX'])->id,
            'parthenon_bank_account_id' => ParthenonBankAccount::firstOrCreate(['bank_name' => 'Banco', 'account_number' => '123'])->id,
        ]);
    }

    private function collaborator(string $name): Collaborator
    {
        $office = SectorOffice::firstOrCreate(
            ['name' => 'Vendedor'],
            ['sector_id' => Sector::firstOrCreate(['name' => 'Comercial'])->id]
        );

        return Collaborator::create([
            'name' => $name,
            'email' => strtolower($name) . '@fastbar.test',
            'phone_1' => '41999999999',
            'sector_id' => $office->sector_id,
            'sector_office_id' => $office->id,
        ]);
    }

    private function place(string $name, City $city): EventPlace
    {
        $place = EventPlace::create(['name' => $name]);

        Address::create([
            'addressable_id' => $place->id,
            'addressable_type' => EventPlace::class,
            'type' => 'main',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
        ]);

        return $place;
    }

    private function statusId(string $name): int
    {
        return EventStatus::where('name', $name)->value('id');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role)->value('id'));

        return $user;
    }

    private function userWithPermissions(array $slugs): User
    {
        $role = Role::create(['name' => 'Gráficos ' . implode(' ', $slugs), 'guard_name' => 'api']);
        $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
