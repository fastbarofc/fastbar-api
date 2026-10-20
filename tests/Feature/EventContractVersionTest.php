<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Event;
use App\Models\EventKit;
use App\Models\EventStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\EventContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EventContractVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'api');
        $this->withoutMiddleware(Admin::class);
    }

    public function test_each_generated_contract_is_recorded_as_a_new_version_with_what_changed(): void
    {
        $event = $this->createPrintableEvent();
        $pdf = storage_path('app/public/pdfs/' . EventContractService::fileName($event) . '.pdf');
        $this->beforeApplicationDestroyed(fn () => File::delete($pdf));

        $this->postJson("/api/events/generate-event-contract/{$event->id}", ['menu_modality' => 'Completa'])
            ->assertOk()
            ->assertJsonStructure(['pdf_url']);

        $first = Contract::where('event_id', $event->id)->sole();

        $this->assertSame(1, $first->version);
        $this->assertSame($event->client_id, $first->client_id);
        $this->assertNull($first->signed_at);
        $this->assertSame([], $first->properties_diff);
        $this->assertSame('Casamento', $first->properties['event_name']);
        $this->assertEquals(['modality' => 'Completa', 'items' => ['Caipirinha']], $first->properties['menu']);
        $this->assertStringContainsString('Cláusula de teste.', $first->body);

        $event->update(['name' => 'Casamento Ana e João']);

        $this->postJson("/api/events/generate-event-contract/{$event->id}", ['menu_modality' => 'Volante'])
            ->assertOk();

        $second = Contract::where('event_id', $event->id)->where('version', 2)->sole();
        $diff = $second->properties_diff;

        $this->assertEquals(['old' => 'Casamento', 'new' => 'Casamento Ana e João'], $diff['modified']['event_name']);
        $this->assertSame('Volante', $diff['modified']['menu']['new']['modality']);
        $this->assertArrayNotHasKey('sections', $diff['modified']);
        $this->assertSame([], $diff['added']);
        $this->assertSame([], $diff['removed']);
    }

    public function test_contract_that_cannot_be_generated_is_not_recorded(): void
    {
        $event = $this->createPrintableEvent();
        $event->eventKits()->delete();

        $this->postJson("/api/events/generate-event-contract/{$event->id}", ['menu_modality' => 'Completa'])
            ->assertUnprocessable();

        $this->assertSame(0, Contract::where('event_id', $event->id)->count());
    }

    public function test_contract_history_lists_versions_newest_first_with_who_generated_and_what_changed(): void
    {
        config(['audit.console' => true]);
        $this->actingAs(User::factory()->create(['name' => 'Ana Souza']), 'api');

        $event = Event::create(['name' => 'Casamento', 'client_id' => Client::factory()->create()->id]);
        Contract::recordVersion($event, ['event_date' => '20/11/2026', 'event_guests' => '100', 'total_price' => '10.000,00', 'menu' => ['modality' => 'Completa']]);
        Contract::recordVersion($event, ['event_date' => '21/11/2026', 'event_guests' => '100', 'total_price' => '12.000,00', 'menu' => ['modality' => 'Volante']]);

        $versions = $this->getJson("/api/events/{$event->id}/contract-history")->assertOk()->json('data');

        $this->assertSame([2, 1], array_column($versions, 'version'));
        $this->assertSame('Ana Souza', $versions[0]['generated_by']);
        $this->assertSame('R$ 12.000,00', $versions[0]['total_price']);
        $this->assertSame([
            ['field' => 'event_date', 'label' => 'Data do evento', 'old' => '20/11/2026', 'new' => '21/11/2026'],
            ['field' => 'total_price', 'label' => 'Valor total', 'old' => 'R$ 10.000,00', 'new' => 'R$ 12.000,00'],
            ['field' => 'menu', 'label' => 'Cardápio', 'old' => null, 'new' => null],
        ], $versions[0]['changes']);
        $this->assertSame([], $versions[1]['changes']);
    }

    private function createPrintableEvent(): Event
    {
        $client = Client::factory()->create(['type' => 'pf', 'cpf' => '52998224725', 'cnpj' => null]);
        $client->address()->create(['type' => AddressTypeEnum::MAIN->value, 'street' => 'Rua das Flores', 'number' => '10']);

        $event = Event::create([
            'name' => 'Casamento',
            'client_id' => $client->id,
            'event_status_id' => EventStatus::create(['name' => 'Contrato'])->id,
            'contract_sections' => [
                'clauses_before_menu' => '<p>Cláusula de teste.</p>',
                'clauses_after_menu' => '<p>Cláusula final.</p>',
            ],
        ]);

        $kit = EventKit::create(['event_id' => $event->id, 'name' => 'Drinks', 'ordination' => 1]);
        $kit->products()->attach(Product::create(['name' => 'Caipirinha']), ['ordination' => 1]);

        return $event->refresh();
    }
}
