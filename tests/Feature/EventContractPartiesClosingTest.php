<?php

namespace Tests\Feature;

use App\Enums\AddressTypeEnum;
use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Event;
use App\Models\User;
use App\Services\EventContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventContractPartiesClosingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'api');
        $this->withoutMiddleware(Admin::class);
    }

    public function test_event_saved_before_the_section_existed_keeps_the_original_closing_in_the_contractant_paragraph(): void
    {
        $event = $this->createEvent(['clauses_before_menu' => '<p>Cláusula de teste.</p>']);

        $this->assertMatchesRegularExpression(
            '/doravante denominado\s+<strong>CONTRATANTE<\/strong>, resolvem assinar o presente CONTRATO, aplicando-se a este suas disposições irrestrita e incondicionalmente, conforme cláusulas e condições seguintes:\s*<\/p>/',
            $this->render($event),
        );
    }

    public function test_edited_closing_continues_the_contractant_paragraph_and_extra_paragraphs_follow_it(): void
    {
        $event = $this->createEvent([
            'parties_closing' => '<p style="text-align: center;">firmam o presente para o evento <strong>{{event_name}}</strong>:</p><p>Parágrafo extra.</p>',
        ]);

        $html = $this->render($event);

        $this->assertMatchesRegularExpression(
            '/doravante denominado\s+<strong>CONTRATANTE<\/strong>, firmam o presente para o evento <strong>Casamento<\/strong>:\s*<\/p>\s*<div class="clauses"><p>Parágrafo extra\.<\/p><\/div>/',
            $html,
        );
        $this->assertStringNotContainsString('resolvem assinar', $html);
    }

    public function test_closing_is_saved_in_the_template_and_falls_back_to_the_original_when_blank(): void
    {
        $sections = [
            'parties_closing' => '<p>acordam o seguinte:</p>',
            'clauses_before_menu' => '<p>Antes.</p>',
            'clauses_after_menu' => '<p>Depois.</p>',
        ];

        $this->postJson('/api/contract-template/save', ['sections' => $sections])
            ->assertOk()
            ->assertJsonPath('sections.parties_closing', '<p>acordam o seguinte:</p>');

        $this->postJson('/api/contract-template/save', ['sections' => ['parties_closing' => '<p>&nbsp;</p>'] + $sections])
            ->assertOk()
            ->assertJsonPath('sections.parties_closing', ContractTemplate::defaultSection('parties_closing'));
    }

    private function createEvent(array $sections): Event
    {
        $client = Client::factory()->create(['type' => 'pf', 'cpf' => '52998224725', 'cnpj' => null]);
        $client->address()->create(['type' => AddressTypeEnum::MAIN->value, 'street' => 'Rua das Flores', 'number' => '10']);

        return Event::create(['name' => 'Casamento', 'client_id' => $client->id, 'contract_sections' => $sections]);
    }

    private function render(Event $event): string
    {
        $contract = EventContractService::build($event, ['menu_modality' => 'Completa']);

        return view('pdfs.event-contract-pdf', ['contract' => $contract, 'brand' => EventContractService::brandAssets()])->render();
    }
}
