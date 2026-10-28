<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class EventProposalSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');

        $this->event = Event::create([
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Aberto'])->id,
        ])->refresh();
    }

    public function test_defaults_bring_the_current_summary_titles(): void
    {
        $this->getJson("/api/events/{$this->event->id}/proposal-data")
            ->assertOk()
            ->assertJsonPath('data.drinks_bar_title', 'BAR DE DRINKS:')
            ->assertJsonPath('data.optional_drinks_title', 'BEBIDAS OPCIONAIS:')
            ->assertJsonPath('data.additional_title', 'ADICIONAIS (troca):');
    }

    public function test_summary_uses_the_edited_title(): void
    {
        $html = $this->renderProposal([
            'drinks_bar_title' => 'BAR DE CAIPIRINHA:',
            'drinks_bar_items' => ['Limão'],
            'drinks_bar_price' => 30,
        ]);

        $this->assertStringContainsString('BAR DE CAIPIRINHA:', $html);
        $this->assertStringNotContainsString('BAR DE DRINKS:', $html);
        $this->assertStringContainsString('Valor por pessoa: R$ 30,00.', $html);
    }

    public function test_blank_title_falls_back_to_the_default(): void
    {
        $html = $this->renderProposal([
            'additional_title' => '  ',
            'additional_items' => ['ABSOLUT +R$ 4,00'],
        ]);

        $this->assertStringContainsString('ADICIONAIS (troca):', $html);
    }

    public function test_optional_drinks_print_one_bullet_per_item_without_price(): void
    {
        $html = $this->renderProposal([
            'optional_drinks_title' => 'BEBIDAS EXTRAS:',
            'optional_drinks_items' => ['Aperol', 'Rum'],
        ]);

        $this->assertStringContainsString('BEBIDAS EXTRAS:', $html);
        $this->assertStringContainsString('<li><span>Aperol</span></li>', $html);
        $this->assertStringContainsString('<li><span>Rum</span></li>', $html);
        $this->assertStringNotContainsString('Valor por pessoa', $html);
    }

    public function test_title_longer_than_60_characters_is_rejected(): void
    {
        $this->postJson("/api/events/generate-proposal/{$this->event->id}", $this->cover() + [
            'additional_title' => str_repeat('A', 61),
            'additional_items' => ['ABSOLUT +R$ 4,00'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('additional_title');
    }

    private function cover(): array
    {
        return [
            'proposal_number' => '1',
            'validity_days' => 30,
            'client_name' => 'Ana',
            'event_name' => 'Casamento',
            'location' => 'Salão',
            'date' => '29/08/26',
            'duration' => '06hs',
            'guests' => '100',
        ];
    }

    private function renderProposal(array $summary): string
    {
        $proposalView = null;
        View::creator('/pdfs/proposal-pdf', function ($view) use (&$proposalView) {
            $proposalView = $view;
        });

        // Binário inexistente faz o wkhtmltopdf falhar antes de gravar o PDF no storage.
        config(['pdf.binary' => '/nonexistent/wkhtmltopdf']);
        $this->postJson("/api/events/generate-proposal/{$this->event->id}", $this->cover() + $summary);

        $this->assertNotNull($proposalView, 'A proposta não foi montada.');

        return $proposalView->render();
    }
}
