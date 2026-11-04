<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventProposal;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EventProposalHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->user = User::factory()->create(['name' => 'Ana Souza']);
        $this->actingAs($this->user, 'api');

        $this->event = Event::create([
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Aberto'])->id,
            'event_guests' => 300,
        ])->refresh();
    }

    public function test_generated_proposal_is_recorded_with_who_generated_it_and_what_was_chosen(): void
    {
        $this->beforeApplicationDestroyed(fn () => File::delete(storage_path("app/public/pdfs/proposta-{$this->event->id}.pdf")));

        $payload = $this->cover(['guests' => '674']) + [
            'observations' => ['Hora adicional: +10% do valor do evento.'],
            'drinks_bar_title' => 'BAR DE DRINKS:',
            'drinks_bar_items' => ['FAST CONCEPT – ATÉ 10 DRINKS'],
            'drinks_bar_price' => 28,
        ];

        $this->postJson("/api/events/generate-proposal/{$this->event->id}", $payload)
            ->assertOk()
            ->assertJsonStructure(['pdf_url']);

        $proposal = EventProposal::where('event_id', $this->event->id)->sole();

        $this->assertSame($this->user->id, $proposal->user_id);
        $this->assertEquals($payload, $proposal->data);
        $this->assertArrayNotHasKey('spirits_bar_items', $proposal->data);
    }

    public function test_proposal_that_fails_to_generate_is_not_recorded(): void
    {
        config(['pdf.binary' => '/nonexistent/wkhtmltopdf']);

        $this->postJson("/api/events/generate-proposal/{$this->event->id}", $this->cover() + [
            'drinks_bar_items' => ['Caipirinha'],
            'drinks_bar_price' => 28,
        ])->assertServerError();

        $this->assertSame(0, EventProposal::count());
    }

    public function test_new_proposal_starts_from_the_latest_one(): void
    {
        $this->recordProposal(
            $this->cover(['guests' => '674']) + ['spirits_bar_items' => ['SMIRNOFF'], 'spirits_bar_price' => 24],
            $this->user->id,
        );
        $latest = $this->recordProposal($this->cover(['guests' => '500']) + [
            'drinks_bar_title' => 'BAR DE CAIPIRINHA:',
            'drinks_bar_items' => ['Limão'],
            'drinks_bar_price' => 30,
        ], $this->user->id);

        $this->getJson("/api/events/{$this->event->id}/proposal-data")
            ->assertOk()
            ->assertJsonPath('data.guests', '500')
            ->assertJsonPath('data.drinks_bar_title', 'BAR DE CAIPIRINHA:')
            ->assertJsonPath('data.drinks_bar_items', ['Limão'])
            ->assertJsonPath('data.drinks_bar_price', 30)
            ->assertJsonPath('data.spirits_bar_items', ['SMIRNOFF', 'GILBEY’S', 'RED LABEL', 'MONSTER'])
            ->assertJsonPath('last_proposal.id', $latest->id)
            ->assertJsonPath('last_proposal.generated_by', 'Ana Souza')
            ->assertJsonMissingPath('last_proposal.data.spirits_bar_items');
    }

    public function test_without_history_the_proposal_starts_from_the_event(): void
    {
        $this->getJson("/api/events/{$this->event->id}/proposal-data")
            ->assertOk()
            ->assertJsonPath('data.guests', '300')
            ->assertJsonPath('last_proposal', null);
    }

    public function test_history_lists_proposals_newest_first_with_the_blocks_that_went_to_the_pdf(): void
    {
        $this->recordProposal($this->cover(['guests' => '674']) + ['additional_items' => ['ABSOLUT +R$ 4,00']], $this->user->id);
        $this->recordProposal($this->cover(['guests' => '500']) + [
            'budget_details' => ['Gelo Incluso.'],
            'drinks_bar_title' => '  ',
            'drinks_bar_items' => ['Limão'],
            'drinks_bar_price' => 30,
        ], null);

        $response = $this->getJson("/api/events/{$this->event->id}/proposal-history")->assertOk();

        $this->assertSame(['500', '674'], array_column(array_column($response->json('data'), 'data'), 'guests'));
        $response
            ->assertJsonPath('data.0.generated_by', null)
            ->assertJsonPath('data.0.blocks', [
                ['title' => 'ORÇAMENTO DETALHADO:', 'items' => ['Gelo Incluso.'], 'line' => null],
                ['title' => 'BAR DE DRINKS:', 'items' => ['Limão'], 'line' => 'Valor por pessoa: R$ 30,00.'],
            ])
            ->assertJsonPath('data.1.generated_by', 'Ana Souza')
            ->assertJsonPath('data.1.blocks', [
                ['title' => 'ADICIONAIS (troca):', 'items' => ['ABSOLUT +R$ 4,00'], 'line' => null],
            ]);
    }

    private function recordProposal(array $data, ?int $userId): EventProposal
    {
        return $this->event->proposals()->create(['data' => $data, 'user_id' => $userId]);
    }

    private function cover(array $overrides = []): array
    {
        return array_merge([
            'proposal_number' => (string) $this->event->id,
            'validity_days' => 30,
            'client_name' => 'Ana',
            'event_name' => 'Casamento',
            'location' => 'Salão',
            'date' => '29/08/26',
            'duration' => '06hs',
            'guests' => '100',
        ], $overrides);
    }
}
