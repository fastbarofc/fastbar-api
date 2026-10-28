<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPrintableStatusTest extends TestCase
{
    use RefreshDatabase;

    private const PRINTS = [
        'generate-closure',
        'generate-summary',
        'generate-menu',
        'generate-menu-with-observations',
        'generate-collaborators',
        'generate-event-checklist',
    ];

    private const BLOCKED_MESSAGE = 'Este PDF só pode ser gerado para eventos em Fechado, Planejamento ou Concluído.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');

        // Binário inexistente faz o wkhtmltopdf falhar antes de gravar o PDF no storage.
        config(['pdf.binary' => '/nonexistent/wkhtmltopdf']);
    }

    public static function printableStatuses(): array
    {
        return [['Fechado'], ['Planejamento'], ['Concluído']];
    }

    /** @dataProvider printableStatuses */
    public function test_prints_are_allowed_from_closed_onwards(string $status): void
    {
        $event = $this->createEvent($status);

        foreach (self::PRINTS as $print) {
            $response = $this->postJson("/api/events/{$print}/{$event->id}");

            $this->assertNotSame(400, $response->status(), "{$print} bloqueado em {$status}");
        }
    }

    public function test_prints_are_blocked_before_closed(): void
    {
        $event = $this->createEvent('Contrato');

        foreach (self::PRINTS as $print) {
            $this->postJson("/api/events/{$print}/{$event->id}")
                ->assertStatus(400)
                ->assertJsonPath('error', self::BLOCKED_MESSAGE);
        }
    }

    private function createEvent(string $status): Event
    {
        return Event::create([
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => $status])->id,
        ])->refresh();
    }
}
