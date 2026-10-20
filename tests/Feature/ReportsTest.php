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
use App\Models\LostReason;
use App\Models\ParthenonBankAccount;
use App\Models\PaymentMethod;
use App\Models\Sector;
use App\Models\SectorOffice;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private EventPlace $curitibaPlace;
    private EventPlace $londrinaPlace;
    private City $curitiba;
    private City $londrina;
    private Client $ana;
    private Client $bruno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs($this->userWithRole('Admin'), 'api');

        $parana = State::create(['name' => 'Paraná', 'letter' => 'PR', 'iso' => 41]);
        $this->curitiba = City::create(['state_id' => $parana->id, 'name' => 'Curitiba', 'iso' => 4106902, 'iso_ddd' => 41]);
        $this->londrina = City::create(['state_id' => $parana->id, 'name' => 'Londrina', 'iso' => 4113700, 'iso_ddd' => 43]);

        $this->curitibaPlace = $this->place('Espaço Batel', $this->curitiba);
        $this->londrinaPlace = $this->place('Villa Londrina', $this->londrina);

        $this->ana = Client::create(['name' => 'Ana Souza', 'type' => 'pf']);
        $this->bruno = Client::create(['name' => 'Bruno Lima', 'type' => 'pf']);
    }

    public function test_events_report_lists_city_and_payment_balance(): void
    {
        $event = $this->event('Fechado', ['name' => 'Casamento Ana', 'total_value' => 10000, 'event_guests' => 150, 'client_id' => $this->ana->id]);
        $this->pay($event, 4000, '2026-05-10');
        $this->pay($event, 1000, '2026-06-10');

        $response = $this->getJson('/api/reports/events')->assertOk();

        $response->assertJsonPath('data.0.name', 'Casamento Ana')
            ->assertJsonPath('data.0.city', 'Curitiba')
            ->assertJsonPath('data.0.state', 'PR')
            ->assertJsonPath('data.0.client', 'Ana Souza')
            ->assertJsonPath('data.0.total_value', 10000)
            ->assertJsonPath('data.0.paid', 5000)
            ->assertJsonPath('data.0.balance', 5000)
            ->assertJsonPath('summary.events', 1)
            ->assertJsonPath('summary.total_value', 10000)
            ->assertJsonPath('summary.balance', 5000)
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_event_without_total_value_has_no_balance(): void
    {
        $this->pay($this->event('Fechado', ['name' => 'Sem valor']), 800);
        $this->pay($this->event('Fechado', ['name' => 'Com valor', 'total_value' => 1000]), 200);

        $response = $this->getJson('/api/reports/events?order_by=name')->assertOk();

        $response->assertJsonPath('data.0.name', 'Com valor')
            ->assertJsonPath('data.0.balance', 800)
            ->assertJsonPath('data.1.name', 'Sem valor')
            ->assertJsonPath('data.1.paid', 800)
            ->assertJsonPath('data.1.balance', null)
            ->assertJsonPath('summary.paid', 1000)
            ->assertJsonPath('summary.balance', 800);
    }

    public function test_events_report_filters_by_city_client_status_and_period(): void
    {
        $this->event('Fechado', ['name' => 'Curitiba Ana', 'client_id' => $this->ana->id, 'date' => '2026-03-10']);
        $this->event('Fechado', ['name' => 'Londrina Ana', 'client_id' => $this->ana->id, 'event_place_id' => $this->londrinaPlace->id, 'date' => '2026-03-12']);
        $this->event('Fechado', ['name' => 'Curitiba Bruno', 'client_id' => $this->bruno->id, 'date' => '2026-03-15']);
        $this->event('Aberto', ['name' => 'Curitiba Ana aberto', 'client_id' => $this->ana->id, 'date' => '2026-03-20']);
        $this->event('Fechado', ['name' => 'Curitiba Ana 2027', 'client_id' => $this->ana->id, 'date' => '2027-01-10']);

        $names = $this->getJson('/api/reports/events?' . http_build_query([
            'city_ids' => (string) $this->curitiba->id,
            'client_ids' => (string) $this->ana->id,
            'status_ids' => (string) $this->statusId('Fechado'),
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
        ]))->assertOk()->json('data.*.name');

        $this->assertSame(['Curitiba Ana'], $names);
    }

    public function test_events_report_sorts_by_city_and_keeps_events_without_place_last(): void
    {
        $this->event('Fechado', ['name' => 'Sem local', 'event_place_id' => null]);
        $this->event('Fechado', ['name' => 'Em Londrina', 'event_place_id' => $this->londrinaPlace->id]);
        $this->event('Fechado', ['name' => 'Em Curitiba']);

        $asc = $this->getJson('/api/reports/events?order_by=city&direction=asc')->json('data.*.name');
        $desc = $this->getJson('/api/reports/events?order_by=city&direction=desc')->json('data.*.name');

        $this->assertSame(['Em Curitiba', 'Em Londrina', 'Sem local'], $asc);
        $this->assertSame(['Em Londrina', 'Em Curitiba', 'Sem local'], $desc);
    }

    public function test_events_report_filters_by_financial_situation(): void
    {
        $paidOff = $this->event('Fechado', ['name' => 'Quitado', 'total_value' => 1000]);
        $partial = $this->event('Fechado', ['name' => 'Parcial', 'total_value' => 1000]);
        $this->event('Fechado', ['name' => 'Sem pagamento', 'total_value' => 1000]);
        $this->pay($paidOff, 1000);
        $this->pay($partial, 300);

        $this->assertSame(['Quitado'], $this->names('/api/reports/events?financial_situation=paid_off'));
        $this->assertEqualsCanonicalizing(['Parcial', 'Sem pagamento'], $this->names('/api/reports/events?financial_situation=with_balance'));
        $this->assertSame(['Sem pagamento'], $this->names('/api/reports/events?financial_situation=no_payment'));
    }

    public function test_revenue_report_groups_by_city_with_share(): void
    {
        $this->event('Fechado', ['total_value' => 6000, 'event_guests' => 100]);
        $this->event('Contrato', ['total_value' => 2000, 'event_guests' => 50]);
        $this->event('Fechado', ['total_value' => 2000, 'event_guests' => 80, 'event_place_id' => $this->londrinaPlace->id]);

        $response = $this->getJson('/api/reports/revenue?group_by=city')->assertOk();

        $response->assertJsonPath('data.0.label', 'Curitiba')
            ->assertJsonPath('data.0.state', 'PR')
            ->assertJsonPath('data.0.group_id', $this->curitiba->id)
            ->assertJsonPath('data.0.events', 2)
            ->assertJsonPath('data.0.guests', 150)
            ->assertJsonPath('data.0.total_value', 8000)
            ->assertJsonPath('data.0.average_ticket', 4000)
            ->assertJsonPath('data.0.share', 0.8)
            ->assertJsonPath('data.1.label', 'Londrina')
            ->assertJsonPath('summary.total_value', 10000);
    }

    public function test_revenue_report_groups_by_client_and_month(): void
    {
        $this->event('Fechado', ['client_id' => $this->ana->id, 'total_value' => 1000, 'date' => '2026-02-10']);
        $this->event('Fechado', ['client_id' => $this->ana->id, 'total_value' => 3000, 'date' => '2026-04-10']);
        $this->event('Fechado', ['client_id' => $this->bruno->id, 'total_value' => 500, 'date' => '2026-02-20']);

        $clients = $this->getJson('/api/reports/revenue?group_by=client')->assertOk()->json('data');
        $this->assertSame(['Ana Souza', 'Bruno Lima'], array_column($clients, 'label'));
        $this->assertSame([4000, 500], array_column($clients, 'total_value'));

        $months = $this->getJson('/api/reports/revenue?group_by=month')->assertOk()->json('data');
        $this->assertSame(['2026-02', '2026-04'], array_column($months, 'label'));
        $this->assertSame(['2026-02', '2026-04'], array_column($months, 'group_id'));
        $this->assertSame($this->ana->id, $clients[0]['group_id']);
        $this->assertSame([1500, 3000], array_column($months, 'total_value'));
    }

    public function test_revenue_report_rejects_unknown_grouping(): void
    {
        $this->getJson('/api/reports/revenue?group_by=lost_reason')
            ->assertJsonValidationErrors(['group_by' => 'Este relatório não pode ser agrupado por esse campo.']);

        $this->getJson('/api/reports/events?period_start=2026-13-01')
            ->assertJsonValidationErrors(['period_start' => 'O campo início do período deve ser uma data válida.']);
    }

    public function test_payments_report_uses_payment_date_and_breaks_down_methods(): void
    {
        $event = $this->event('Fechado', ['name' => 'Formatura', 'total_value' => 9000]);
        $pix = PaymentMethod::create(['name' => 'PIX']);
        $card = PaymentMethod::create(['name' => 'Cartão']);
        $this->pay($event, 3000, '2026-05-02', $pix);
        $this->pay($event, 1000, '2026-05-20', $card);
        $this->pay($event, 500, '2026-06-01', $pix);

        $response = $this->getJson('/api/reports/payments?period_start=2026-05-01&period_end=2026-05-31')->assertOk();

        $response->assertJsonPath('summary.total', 4000)
            ->assertJsonPath('summary.payments', 2)
            ->assertJsonPath('summary.events', 1)
            ->assertJsonPath('summary.by_method.0.label', 'PIX')
            ->assertJsonPath('summary.by_method.0.share', 0.75)
            ->assertJsonPath('data.0.date', '2026-05-20')
            ->assertJsonPath('data.0.event_name', 'Formatura')
            ->assertJsonPath('data.0.city', 'Curitiba')
            ->assertJsonPath('data.0.payment_method', 'Cartão');

        $this->getJson("/api/reports/payments?payment_method_ids={$pix->id}")
            ->assertJsonPath('summary.total', 3500);
    }

    public function test_sales_report_counts_pipeline_and_conversion_per_collaborator(): void
    {
        $carla = $this->collaborator('Carla');
        $diego = $this->collaborator('Diego');

        foreach (['Fechado', 'Concluído', 'Cancelado', 'Aberto'] as $status) {
            $this->event($status, ['collaborator_id' => $carla->id, 'total_value' => 1000]);
        }
        $this->event('Fechou com o concorrente', ['collaborator_id' => $diego->id]);

        $response = $this->getJson('/api/reports/sales?group_by=collaborator')->assertOk();

        $response->assertJsonPath('data.0.label', 'Carla')
            ->assertJsonPath('data.0.total', 4)
            ->assertJsonPath('data.0.confirmed', 2)
            ->assertJsonPath('data.0.lost', 1)
            ->assertJsonPath('data.0.negotiating', 1)
            ->assertJsonPath('data.0.conversion_rate', 0.6667)
            ->assertJsonPath('data.0.confirmed_value', 2000)
            ->assertJsonPath('data.0.pipeline_value', 1000)
            ->assertJsonPath('data.1.label', 'Diego')
            ->assertJsonPath('data.1.conversion_rate', 0)
            ->assertJsonPath('summary.total', 5)
            ->assertJsonPath('summary.conversion_rate', 0.5);
    }

    public function test_losses_report_groups_lost_events_by_reason(): void
    {
        $price = LostReason::create(['name' => 'Preço']);
        $date = LostReason::create(['name' => 'Data indisponível']);
        $rival = CompetingCompany::create(['name' => 'Bar Rival']);

        $this->event('Cancelado', ['lost_reason_id' => $price->id]);
        $this->event('Fechou com o concorrente', ['lost_reason_id' => $price->id, 'competing_company_id' => $rival->id]);
        $this->event('Descartado', ['lost_reason_id' => $date->id]);
        $this->event('Fechado');

        $response = $this->getJson('/api/reports/losses')->assertOk();

        $response->assertJsonPath('data.0.label', 'Preço')
            ->assertJsonPath('data.0.events', 2)
            ->assertJsonPath('data.0.share', 0.6667)
            ->assertJsonPath('data.1.label', 'Data indisponível')
            ->assertJsonPath('summary.events', 3)
            ->assertJsonPath('summary.loss_rate', 0.75)
            ->assertJsonPath('summary.lost_to_competitor', 1)
            ->assertJsonPath('summary.main_reason', 'Preço');

        $this->getJson("/api/reports/losses?lost_reason_ids={$price->id}")
            ->assertJsonPath('summary.events', 2)
            ->assertJsonPath('summary.loss_rate', 0.5);

        $this->getJson('/api/reports/losses?group_by=competitor')
            ->assertJsonPath('data.0.label', 'Sem concorrente')
            ->assertJsonPath('data.1.label', 'Bar Rival');
    }

    public function test_export_returns_every_row_without_pagination(): void
    {
        foreach (range(1, 30) as $i) {
            $this->event('Fechado', ['name' => "Evento {$i}"]);
        }

        $this->assertCount(25, $this->getJson('/api/reports/events')->json('data'));
        $this->assertCount(30, $this->getJson('/api/reports/events/export')->assertOk()->json('data'));
    }

    public function test_reports_require_their_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['relatorios-comercial']), 'api');

        $this->getJson('/api/reports/sales')->assertOk();
        $this->getJson('/api/reports/events')->assertForbidden();
        $this->getJson('/api/reports/sales/export')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['relatorios-financeiro', 'relatorios-exportar']), 'api');

        $this->getJson('/api/reports/events/export')->assertOk();
        $this->getJson('/api/reports/losses')->assertForbidden();
    }

    public function test_filter_options_classify_statuses(): void
    {
        $statuses = collect($this->getJson('/api/reports/filter-options')->assertOk()->json('statuses'))->pluck('group', 'name');

        $this->assertSame('confirmed', $statuses['Contrato']);
        $this->assertSame('negotiating', $statuses['Pré-Reserva']);
        $this->assertSame('lost', $statuses['Fechou com o concorrente']);

        $cities = collect($this->getJson('/api/reports/filter-options')->json('cities'))->pluck('name')->all();
        $this->assertSame(['Curitiba', 'Londrina'], $cities);
    }

    public function test_permissions_migration_keeps_access_of_event_report_groups(): void
    {
        $this->assertTrue(Permission::where('slug', 'relatorios-financeiro')->exists());
        $this->assertTrue(Permission::where('slug', 'relatorios-comercial')->exists());
        $this->assertTrue(Permission::where('slug', 'relatorios-exportar')->exists());
        $this->assertFalse(Permission::where('slug', 'like', 'relatorios-gerar-relatorio-%')->exists());
        $this->assertTrue(Role::where('name', 'Admin')->first()->permissions->contains('slug', 'relatorios-exportar'));
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

    private function pay(Event $event, float $value, string $date = '2026-06-01', ?PaymentMethod $method = null): void
    {
        EventPayment::create([
            'event_id' => $event->id,
            'value' => $value,
            'date' => $date,
            'payment_method_id' => ($method ?? PaymentMethod::firstOrCreate(['name' => 'PIX']))->id,
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

    private function names(string $url): array
    {
        return $this->getJson($url)->assertOk()->json('data.*.name');
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
        $role = Role::create(['name' => 'Relatórios ' . implode(' ', $slugs), 'guard_name' => 'api']);
        $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
