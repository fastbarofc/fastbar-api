<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventNote;
use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class EventChecklistNotesTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');

        $this->event = Event::create([
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Fechado'])->id,
        ])->refresh();
    }

    public function test_note_is_marked_for_checklist_by_default(): void
    {
        $this->postJson('/api/event-notes', [
            'text' => 'Levar gelo extra',
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
        ])
            ->assertCreated()
            ->assertJsonPath('checklist', true);
    }

    public function test_user_can_toggle_note_checklist_without_sending_the_text(): void
    {
        $note = $this->createNote('Levar gelo extra', true);

        $this->putJson("/api/event-notes/{$note->id}", ['checklist' => false])
            ->assertOk()
            ->assertJsonPath('checklist', false)
            ->assertJsonPath('text', 'Levar gelo extra');

        $this->assertFalse($note->fresh()->checklist);
    }

    public function test_checklist_pdf_lists_only_notes_marked_for_checklist(): void
    {
        $this->createNote('Levar gelo extra', true);
        $this->createNote('Cliente pediu desconto', false);

        $html = $this->renderChecklist();

        $this->assertStringContainsString('OBSERVAÇÕES</th>', $html);
        $this->assertStringContainsString('Levar gelo extra', $html);
        $this->assertStringNotContainsString('Cliente pediu desconto', $html);
    }

    public function test_checklist_pdf_hides_notes_table_when_no_note_is_marked(): void
    {
        $this->createNote('Cliente pediu desconto', false);

        $html = $this->renderChecklist();

        $this->assertStringNotContainsString('OBSERVAÇÕES</th>', $html);
    }

    private function createNote(string $text, bool $checklist): EventNote
    {
        return EventNote::create([
            'text' => $text,
            'checklist' => $checklist,
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
        ]);
    }

    private function renderChecklist(): string
    {
        $checklistView = null;
        View::creator('/pdfs/event-checklist-pdf', function ($view) use (&$checklistView) {
            $checklistView = $view;
        });

        // Binário inexistente faz o wkhtmltopdf falhar antes de gravar o PDF no storage.
        config(['pdf.binary' => '/nonexistent/wkhtmltopdf']);
        $this->postJson("/api/events/generate-event-checklist/{$this->event->id}");

        $this->assertNotNull($checklistView, 'O checklist não foi montado.');

        return $checklistView->render();
    }
}
