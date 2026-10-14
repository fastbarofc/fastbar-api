<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use App\Services\EventContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EventContractBarServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_contract_prints_bartenders_cups_and_bar_counter_from_the_event(): void
    {
        $event = $this->createEvent([
            'duration' => 5,
            'glass_cup_hours' => 4,
            'guests_per_bartender' => 50,
            'cup_type' => 'premium_glass',
            'bar_counter_type' => '360',
        ]);

        $sections = EventContractService::build($event, ['menu_modality' => 'Completa'])['sections'];
        $after = $sections['clauses_after_menu'];

        $this->assertStringContainsString('Tipo de balcão: 360º</p>', $sections['clauses_before_menu']);
        $this->assertStringContainsString('ora contratados, mais especificamente 1 (um) barman a cada 50 convidados.</p>', $after);
        $this->assertStringContainsString('<p>22.2 – Copos de vidro linha premium necessários para a realização de 4 horas iniciais de evento e copos de acrílico para as 1 horas finais de evento.</p>', $after);
        $this->assertStringContainsString('<p>22.3 – Balcões necessários para a montagem de 1 (um) ponto de bar do tipo "360º".</p>', $after);
    }

    public function test_contract_generation_asks_for_the_fields_the_event_left_empty(): void
    {
        $this->withoutMiddleware(Admin::class);
        $labels = ['Convidados por barman', 'Tipo de copo', 'Tipo do balcão'];

        $empty = $this->createEvent();
        $missing = $this->postJson("/api/events/generate-event-contract/{$empty->id}", ['menu_modality' => 'Completa'])
            ->assertUnprocessable()
            ->json('missing_fields');

        foreach ($labels as $label) {
            $this->assertContains($label, $missing);
        }

        $filled = $this->createEvent(['guests_per_bartender' => 50, 'cup_type' => 'acrylic', 'bar_counter_type' => 'lateral']);
        $missing = $this->postJson("/api/events/generate-event-contract/{$filled->id}", ['menu_modality' => 'Completa'])
            ->json('missing_fields') ?? [];

        foreach ($labels as $label) {
            $this->assertNotContains($label, $missing);
        }
    }

    public function test_event_saves_with_the_fields_empty_or_with_known_types(): void
    {
        $rules = (new UpdateEventRequest())->rules();

        $this->assertTrue(Validator::make(['bar_counter_type' => null, 'cup_type' => null, 'guests_per_bartender' => null], $rules)->passes());
        $this->assertTrue(Validator::make(['bar_counter_type' => '360', 'cup_type' => 'acrylic', 'guests_per_bartender' => 40], $rules)->passes());
        $this->assertTrue(Validator::make(['bar_counter_type' => 'curvo'], $rules)->fails());
        $this->assertTrue(Validator::make(['cup_type' => 'glass'], $rules)->fails());
        $this->assertTrue(Validator::make(['guests_per_bartender' => 0], $rules)->fails());
    }

    public function test_migration_rewrites_saved_template_and_keeps_hand_edited_clauses(): void
    {
        $migration = require database_path('migrations/2026_10_14_000002_add_bar_service_fields_to_contract_template.php');
        $defaults = ContractTemplate::current()->sectionsWith();
        $template = ContractTemplate::create(['sections' => $defaults]);

        $migration->down();
        $old = $template->fresh()->sections;

        $this->assertStringContainsString('ponto de bar do tipo "lateral".</p>', $old['clauses_after_menu']);
        $this->assertStringNotContainsString('{{bar_counter_type}}', $old['clauses_before_menu'] . $old['clauses_after_menu']);

        $migration->up();

        $this->assertEquals($defaults, $template->fresh()->sections);

        $edited = str_replace('22.1 - A equipe de bartenders', '22.1 - Nossa equipe', $old['clauses_after_menu']);
        $template->update(['sections' => ['clauses_before_menu' => $old['clauses_before_menu'], 'clauses_after_menu' => $edited]]);

        $migration->up();

        $this->assertSame($edited, $template->fresh()->sections['clauses_after_menu']);
        $this->assertStringContainsString('Tipo de balcão: {{bar_counter_type}}</p>', $template->fresh()->sections['clauses_before_menu']);
    }

    private function createEvent(array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Contrato'])->id,
        ])->refresh();
    }
}
