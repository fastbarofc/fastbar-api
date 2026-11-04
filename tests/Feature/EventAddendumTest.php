<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventAddendum;
use App\Models\EventBatch;
use App\Models\EventHistory;
use App\Models\EventKit;
use App\Models\EventStatus;
use App\Models\Kit;
use App\Models\Product;
use App\Models\User;
use App\Services\EventAddendumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EventAddendumTest extends TestCase
{
    use RefreshDatabase;

    private Product $caipirinha;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'api');
        $this->withoutMiddleware(Admin::class);
    }

    public function test_addendum_changes_the_event_values_and_records_what_was_agreed(): void
    {
        $event = $this->createEvent();

        $this->postJson("/api/events/{$event->id}/addendums", [
            'types' => ['unit_price', 'guests', 'value', 'service'],
            'unit_price' => 120,
            'guests_added' => 20,
            'value_added' => 500,
            'value_description' => 'deslocamento',
            'services' => [
                ['description' => 'Drink de boas-vindas', 'value' => 300],
                ['description' => 'Bar de café', 'value' => null],
            ],
            'notes' => "Balcão lateral na cor preta.\n",
            'payment_terms' => 'A diferença será paga junto da última parcela.',
        ])
            ->assertCreated()
            ->assertJsonPath('code', "{$event->id}-1")
            ->assertJsonPath('previous_total', 10200)
            ->assertJsonPath('new_total', 15400);

        $event->refresh();

        // 120 convidados a R$ 120 = 14.400, mais 200 do lote, 500 de valor e 300 do serviço.
        $this->assertEquals(120, $event->event_guests);
        $this->assertEquals(120, $event->guest_unit_value);
        $this->assertEquals(800, $event->addendum_value);
        $this->assertEquals(14400, $event->buffet_value);
        $this->assertEquals(14400, $event->sub_total);
        $this->assertEquals(15400, $event->total_value);

        $addendum = EventAddendum::where('event_id', $event->id)->sole();
        $document = $addendum->document;

        $this->assertSame(['unit_price', 'guests', 'value', 'service'], $addendum->types);
        $this->assertSame([
            'Alteração do valor por convidado de R$ 100,00 para R$ 120,00;',
            'Acréscimo de 20 convidados, passando de 100 para 120 convidados;',
            'Acréscimo de R$ 500,00 ao valor do contrato, referente a deslocamento;',
            'Inclusão do serviço adicional: Drink de boas-vindas (R$ 300,00);',
            'Inclusão do serviço adicional: Bar de café;',
            'Balcão lateral na cor preta;',
        ], array_column($document['items'], 'text'));
        $this->assertSame(
            'Pela alteração ora pactuada, o valor total do contrato passa de R$ 10.200,00 para R$ 15.400,00, um acréscimo de R$ 5.200,00.',
            $document['value_clause']
        );
        $this->assertSame('A diferença será paga junto da última parcela.', $document['payment_terms']);
        $this->assertSame('122', $document['event']['guests']);
        $this->assertSame('Ana Souza', $document['contractant']['name']);

        $history = EventHistory::where('event_addendum_id', $addendum->id)->sole();
        $this->assertEquals(
            ['field' => 'total_value', 'label' => 'Valor Total', 'old' => 'R$ 10.200,00', 'new' => 'R$ 15.400,00'],
            collect($history->changes)->firstWhere('field', 'total_value')
        );

        $this->getJson("/api/events/{$event->id}/history")
            ->assertOk()
            ->assertJsonPath('data.0.action_label', "Aditivo {$event->id}-1");
    }

    public function test_each_addendum_of_the_event_gets_the_next_number(): void
    {
        $event = $this->createEvent();

        $this->postJson("/api/events/{$event->id}/addendums", ['types' => ['guests'], 'guests_added' => 10])->assertCreated();
        $this->postJson("/api/events/{$event->id}/addendums", ['notes' => 'Balcão preto'])
            ->assertCreated()
            ->assertJsonPath('code', "{$event->id}-2")
            ->assertJsonPath('items.0.text', 'Balcão preto;');

        $this->getJson("/api/events/{$event->id}/addendums")
            ->assertOk()
            ->assertJsonPath('data.0.code', "{$event->id}-2")
            ->assertJsonPath('data.1.code', "{$event->id}-1")
            ->assertJsonPath('context.can_create', true)
            ->assertJsonPath('context.event_guests', 110);
    }

    public function test_menu_addendum_replaces_the_event_drinks_with_the_chosen_ones(): void
    {
        $event = $this->createEvent();
        $folder = EventKit::where('event_id', $event->id)->sole();

        $plan = Kit::create(['name' => 'Plano Fast Concept']);
        $plan->products()->attach(Product::create(['name' => 'Fast fruit']), ['ordination' => 1]);
        $plan->products()->attach(Product::create(['name' => 'Mojito']), ['ordination' => 2]);
        $classics = Kit::create(['name' => 'Clássicos']);
        $negroni = Product::create(['name' => 'Negroni']);
        $classics->products()->attach($negroni, ['ordination' => 1]);

        $this->postJson("/api/events/{$event->id}/addendums", [
            'types' => ['menu'],
            'menu_modality' => 'PLANO FAST CONCEPT',
            'menu_remove' => [['event_kit_id' => $folder->id, 'product_id' => $this->caipirinha->id]],
            'menu_add' => [
                ['kit_id' => $plan->id, 'product_id' => null],
                ['kit_id' => $classics->id, 'product_id' => $negroni->id],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('items.0.text', 'Fica definido o cardápio a ser executado no evento: PLANO FAST CONCEPT;')
            ->assertJsonPath('items.0.children', ['Fast fruit', 'Mojito', 'Negroni'])
            ->assertJsonPath('new_total', 10200);

        $event->refresh();

        $this->assertSoftDeleted($folder);
        $this->assertSame('PLANO FAST CONCEPT', $event->contract_print_data['menu_modality']);
        $this->assertEquals(10200, $event->total_value);

        $labels = collect(EventHistory::whereNotNull('event_addendum_id')->sole()->changes)->pluck('label');
        $this->assertContains('Modalidade do Cardápio', $labels);
        $this->assertContains('Menu de drinks: pasta removida', $labels);
        $this->assertContains('Menu de drinks: pasta adicionada', $labels);
    }

    public function test_menu_addendum_cannot_leave_the_event_without_drinks(): void
    {
        $event = $this->createEvent();
        $folder = EventKit::where('event_id', $event->id)->sole();

        $this->postJson("/api/events/{$event->id}/addendums", [
            'types' => ['menu', 'guests'],
            'guests_added' => 5,
            'menu_modality' => 'Completa',
            'menu_remove' => [['event_kit_id' => $folder->id, 'product_id' => $this->caipirinha->id]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('menu_add');

        $this->assertSame(0, EventAddendum::count());
        $this->assertEquals(100, $event->refresh()->event_guests);
        $this->assertTrue($folder->products()->where('products.id', $this->caipirinha->id)->exists());
    }

    public function test_each_chosen_type_must_change_something_in_the_event(): void
    {
        $event = $this->createEvent();

        $this->postJson("/api/events/{$event->id}/addendums", [
            'types' => ['guests', 'unit_price', 'menu'],
            'guests_added' => 5,
            'unit_price' => 100,
            'menu_modality' => 'Completa',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unit_price', 'menu']);

        $this->assertSame(0, EventAddendum::count());
        $this->assertEquals(100, $event->refresh()->event_guests);

        $this->postJson("/api/events/{$event->id}/addendums", ['types' => ['menu'], 'menu_modality' => 'Premium'])
            ->assertCreated()
            ->assertJsonPath('items.0.children', ['Caipirinha']);
    }

    public function test_addendum_requires_the_fields_of_each_chosen_type(): void
    {
        $event = $this->createEvent();

        $this->postJson("/api/events/{$event->id}/addendums", ['types' => ['unit_price', 'guests', 'menu', 'value', 'service']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unit_price', 'guests_added', 'menu_modality', 'value_added', 'services']);

        $this->postJson("/api/events/{$event->id}/addendums", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('types');
    }

    public function test_addendum_is_only_allowed_once_the_event_has_a_contract(): void
    {
        $event = $this->createEvent('Pré-Reserva');

        $this->postJson("/api/events/{$event->id}/addendums", ['types' => ['guests'], 'guests_added' => 10])
            ->assertUnprocessable();

        $this->assertSame(0, EventAddendum::count());
        $this->getJson("/api/events/{$event->id}/addendums")->assertJsonPath('context.can_create', false);
    }

    public function test_addendum_pdf_is_generated_from_the_recorded_document(): void
    {
        $event = $this->createEvent();
        $this->postJson("/api/events/{$event->id}/addendums", ['types' => ['guests'], 'guests_added' => 10])->assertCreated();
        $addendum = EventAddendum::sole();

        $pdf = storage_path('app/public/pdfs/' . EventAddendumService::fileName($addendum) . '.pdf');
        $this->beforeApplicationDestroyed(fn () => File::delete($pdf));

        $this->postJson("/api/events/{$event->id}/addendums/{$addendum->id}/pdf")
            ->assertOk()
            ->assertJsonStructure(['pdf_url']);

        $this->assertFileExists($pdf);

        $other = $this->createEvent();
        $this->postJson("/api/events/{$other->id}/addendums/{$addendum->id}/pdf")->assertNotFound();
    }

    private function createEvent(string $status = 'Fechado'): Event
    {
        $client = Client::factory()->create(['name' => 'Ana Souza', 'type' => 'pf', 'cpf' => '52998224725', 'cnpj' => null]);
        $client->address()->create(['type' => AddressTypeEnum::MAIN->value, 'street' => 'Rua das Flores', 'number' => '10']);

        $event = Event::create([
            'name' => 'Casamento',
            'client_id' => $client->id,
            'event_status_id' => EventStatus::firstOrCreate(['name' => $status])->id,
            'event_guests' => 100,
            'guest_unit_value' => 100,
            'buffet_value' => 10000,
            'sub_total' => 10000,
            'total_value' => 10200,
            'contract_print_data' => ['menu_modality' => 'Completa'],
        ]);

        EventBatch::create(['event_id' => $event->id, 'name' => 'Lote 1', 'guests' => 2, 'unit_value' => 100]);

        $this->caipirinha = Product::firstOrCreate(['name' => 'Caipirinha']);
        $kit = EventKit::create(['event_id' => $event->id, 'name' => 'Drinks', 'ordination' => 1]);
        $kit->products()->attach($this->caipirinha, ['ordination' => 1]);

        return $event->refresh();
    }
}
