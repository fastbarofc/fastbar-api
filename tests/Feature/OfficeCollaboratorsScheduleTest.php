<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Collaborator;
use App\Models\Event;
use App\Models\OfficeBudget;
use App\Models\OfficeEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeCollaboratorsScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_saving_one_event_schedule_keeps_the_other_events(): void
    {
        foreach (['office-events' => $this->officeEvent(), 'office-budgets' => OfficeBudget::create(['name' => 'Garçom', 'slug' => 'garcom'])] as $uri => $office) {
            [$eventA, $eventB] = [Event::create(['name' => 'A']), Event::create(['name' => 'B'])];
            [$ana, $bia, $caio] = Collaborator::factory()->count(3)->create()->all();

            $this->postJson("/api/{$uri}/{$office->id}/attach-collaborators", ['eventId' => $eventB->id, 'collaborators' => [$ana->id]])->assertOk();
            $this->postJson("/api/{$uri}/{$office->id}/attach-collaborators", ['eventId' => $eventA->id, 'collaborators' => [$bia->id, $caio->id]])->assertOk();
            $this->postJson("/api/{$uri}/{$office->id}/attach-collaborators", ['eventId' => $eventA->id, 'collaborators' => [$caio->id]])->assertOk();

            $this->assertSame([$caio->id], $this->scheduled($office, $eventA));
            $this->assertSame([$ana->id], $this->scheduled($office, $eventB));

            $this->postJson("/api/{$uri}/{$office->id}/attach-collaborators", ['eventId' => $eventB->id, 'collaborators' => [$caio->id]])->assertOk();
            $this->postJson("/api/{$uri}/{$office->id}/detach-collaborator", ['event_id' => $eventA->id, 'collaborator_id' => $caio->id])->assertOk();

            $this->assertSame([], $this->scheduled($office, $eventA));
            $this->assertSame([$caio->id], $this->scheduled($office, $eventB));
        }
    }

    public function test_invalid_schedule_is_rejected_without_touching_the_current_one(): void
    {
        $office = $this->officeEvent();
        $event = Event::create(['name' => 'A']);
        $collaborator = Collaborator::factory()->create();
        $this->postJson("/api/office-events/{$office->id}/attach-collaborators", ['eventId' => $event->id, 'collaborators' => [$collaborator->id]])->assertOk();

        $this->postJson("/api/office-events/{$office->id}/attach-collaborators", ['eventId' => $event->id])
            ->assertJsonValidationErrors(['collaborators' => 'Informe a lista de colaboradores.']);
        $this->postJson("/api/office-events/{$office->id}/attach-collaborators", ['eventId' => $event->id, 'collaborators' => [999999]])
            ->assertJsonValidationErrors(['collaborators.0' => 'Um dos colaboradores informados não existe.']);

        $this->assertSame([$collaborator->id], $this->scheduled($office, $event));
    }

    private function officeEvent(): OfficeEvent
    {
        return OfficeEvent::create(['description' => 'Bartender', 'daily' => false, 'coordinator' => false, 'date' => false]);
    }

    private function scheduled(OfficeEvent|OfficeBudget $office, Event $event): array
    {
        return $office->collaborators()->wherePivot('event_id', $event->id)->pluck('collaborators.id')->sort()->values()->all();
    }
}
