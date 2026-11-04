<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Http\Middleware\Admin;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientRepresentative;
use App\Models\Contract;
use App\Models\Event;
use App\Models\EventAddendum;
use App\Models\EventKit;
use App\Models\EventStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\EventContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EventContractRepresentativesTest extends TestCase
{
    use RefreshDatabase;

    private ClientRepresentative $joao;

    private ClientRepresentative $maria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'api');
        $this->withoutMiddleware(Admin::class);
    }

    public function test_contract_data_lists_the_representatives_and_starts_with_all_of_them_marked(): void
    {
        $event = $this->createEvent();
        $this->maria->address->update(['street' => '', 'number' => '', 'district' => '', 'zip_code' => '', 'city_id' => null, 'state_id' => null]);

        $data = $this->getJson("/api/events/{$event->id}/contract-data")->assertOk()->json('data');

        $this->assertSame([$this->joao->id, $this->maria->id], $data['representative_ids']);
        $this->assertSame([
            ['id' => $this->joao->id, 'name' => 'João Silva', 'cpf' => '529.982.247-25', 'missing_fields' => []],
            ['id' => $this->maria->id, 'name' => 'Maria Souza', 'cpf' => '153.509.460-56', 'missing_fields' => ['Endereço']],
        ], $data['representatives']);
        $this->assertSame([], $data['missing_fields']);
    }

    public function test_contract_shows_only_the_marked_representatives_and_remembers_them(): void
    {
        $event = $this->createEvent();
        $this->deletePdfAfterTest($event);

        $this->postJson("/api/events/generate-event-contract/{$event->id}", [
            'menu_modality' => 'Completa',
            'representative_ids' => [$this->maria->id],
        ])->assertOk();

        $contractant = Contract::where('event_id', $event->id)->sole()->properties['contractant'];

        $this->assertSame(['Maria Souza'], array_column($contractant['representatives'], 'name'));
        $this->getJson("/api/events/{$event->id}/contract-data")->assertJsonPath('data.representative_ids', [$this->maria->id]);
    }

    public function test_contract_names_every_marked_representative_in_the_preamble_and_in_the_signatures(): void
    {
        $event = $this->createEvent();

        $contract = EventContractService::build($event, [
            'menu_modality' => 'Completa',
            'representative_ids' => [$this->joao->id, $this->maria->id],
        ]);
        $html = view('pdfs.event-contract-pdf', ['contract' => $contract, 'brand' => EventContractService::brandAssets()])->render();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString(
            'neste ato representada por João Silva, CPF 529.982.247-25, residente e domiciliado na Rua A, n° 10, Bairro Centro',
            $text,
        );
        $this->assertMatchesRegularExpression('/CEP 01001-000; e por Maria Souza, CPF 153\.509\.460-56, residente e domiciliado na Rua B.*?CEP 01001-000, doravante denominado CONTRATANTE/', $text);
        $this->assertStringContainsString('Representado por: João Silva CPF: 529.982.247-25 CONTRATANTE', $text);
        $this->assertStringContainsString('Representado por: Maria Souza CPF: 153.509.460-56 CONTRATANTE', $text);
    }

    public function test_company_contract_needs_a_marked_and_complete_representative(): void
    {
        $event = $this->createEvent();
        $this->maria->update(['cpf' => null]);

        $this->postJson("/api/events/generate-event-contract/{$event->id}", ['menu_modality' => 'Completa', 'representative_ids' => []])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Marque pelo menos um representante legal para aparecer no contrato.');

        $this->postJson("/api/events/generate-event-contract/{$event->id}", [
            'menu_modality' => 'Completa',
            'representative_ids' => [$this->joao->id, $this->maria->id],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('missing_fields', ['CPF do representante legal Maria Souza']);

        $this->assertSame(0, Contract::where('event_id', $event->id)->count());
    }

    public function test_company_without_representatives_cannot_generate_the_contract(): void
    {
        $event = $this->createEvent();
        $event->client->representatives->each->delete();

        $this->getJson("/api/events/{$event->id}/contract-data")->assertJsonPath('data.missing_fields', ['Representante legal']);
    }

    public function test_addendum_brings_the_representatives_marked_in_the_contract(): void
    {
        $event = $this->createEvent();
        $event->update([
            'event_status_id' => EventStatus::firstOrCreate(['name' => 'Fechado'])->id,
            'contract_print_data' => ['menu_modality' => 'Completa', 'representative_ids' => [$this->joao->id]],
            'event_guests' => 100,
            'guest_unit_value' => 100,
        ]);

        $this->postJson("/api/events/{$event->id}/addendums", ['types' => ['guests'], 'guests_added' => 10])->assertCreated();

        $document = EventAddendum::sole()->document;
        $html = view('pdfs.event-addendum-pdf', [
            'document' => $document,
            'code' => '1-1',
            'brand' => EventContractService::brandAssets(),
        ])->render();

        $this->assertSame(['João Silva'], array_column($document['contractant']['representatives'], 'name'));
        $this->assertStringContainsString('Representada por: João Silva', $html);
        $this->assertStringNotContainsString('Maria Souza', $html);
    }

    public function test_addendum_recorded_with_a_single_representative_still_renders(): void
    {
        $html = view('pdfs.event-addendum-pdf', [
            'document' => [
                'issued_at' => '2026-10-30',
                'contract_code' => '1',
                'contractant' => [
                    'is_company' => true,
                    'name' => 'Empresa Ltda',
                    'document' => '11.222.333/0001-81',
                    'rg' => null,
                    'address' => 'Rua Sede',
                    'representative' => ['name' => 'João Silva', 'cpf' => '529.982.247-25', 'address' => 'Rua A'],
                ],
                'event' => ['name' => null, 'place' => null, 'date' => null, 'time' => null, 'duration' => null, 'guests' => null],
                'items' => [],
                'value_clause' => 'Sem alteração.',
                'payment_terms' => null,
            ],
            'code' => '1-1',
            'brand' => EventContractService::brandAssets(),
        ])->render();

        $this->assertStringContainsString('Representada por: João Silva', $html);
    }

    private function createEvent(): Event
    {
        $client = Client::create([
            'name' => 'Empresa Ltda',
            'type' => 'pj',
            'cnpj' => '11222333000181',
            'phone_1' => '11999999999',
        ]);
        $this->createAddress($client, 'Rua Sede', AddressTypeEnum::MAIN);

        $this->joao = $client->representatives()->create(['name' => 'João Silva', 'cpf' => '52998224725']);
        $this->createAddress($this->joao, 'Rua A', AddressTypeEnum::REPRESENTATIVE);
        $this->maria = $client->representatives()->create(['name' => 'Maria Souza', 'cpf' => '15350946056']);
        $this->createAddress($this->maria, 'Rua B', AddressTypeEnum::REPRESENTATIVE);

        $event = Event::create([
            'name' => 'Convenção',
            'client_id' => $client->id,
            'event_status_id' => EventStatus::firstOrCreate(['name' => 'Contrato'])->id,
            'contract_sections' => [
                'clauses_before_menu' => '<p>Cláusula de teste.</p>',
                'clauses_after_menu' => '<p>Cláusula final.</p>',
            ],
        ]);

        $kit = EventKit::create(['event_id' => $event->id, 'name' => 'Drinks', 'ordination' => 1]);
        $kit->products()->attach(Product::create(['name' => 'Caipirinha']), ['ordination' => 1]);

        return $event->refresh();
    }

    private function createAddress($addressable, string $street, AddressTypeEnum $type): void
    {
        $city = City::first();

        $addressable->address()->create([
            'type' => $type->value,
            'street' => $street,
            'number' => '10',
            'district' => 'Centro',
            'zip_code' => '01001-000',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
        ]);
    }

    private function deletePdfAfterTest(Event $event): void
    {
        $pdf = storage_path('app/public/pdfs/' . EventContractService::fileName($event) . '.pdf');
        $this->beforeApplicationDestroyed(fn () => File::delete($pdf));
    }
}
