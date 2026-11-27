<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Collaborator;
use App\Models\Event;
use App\Models\EventNote;
use App\Models\EventStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\EventHistoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EventPayloadValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    public function test_values_the_database_would_reject_come_back_as_validation_errors(): void
    {
        $event = $this->createEvent();

        $cases = [
            [['client_id' => 999999], ['client_id' => 'O cliente informado não existe.']],
            [['total_value' => 100000000], ['total_value' => 'O valor total não pode passar de 99.999.999,99.']],
            [['guests_per_bartender' => 70000], ['guests_per_bartender']],
            [['event_batches' => [['guests' => 100, 'unit_value' => null]]], ['event_batches.0.unit_value' => 'Informe o valor por convidado do lote.']],
            [['materials' => [['id' => 999999, 'selected' => '1', 'quantity' => 1]]], ['materials.0.id']],
            [['extra_sales' => [['description' => null, 'quantity' => 1, 'unit_value' => 10]]], ['extra_sales.0.description']],
            [['payment_plan' => $this->paymentPlan(['due_day' => 100])], ['payment_plan.due_day' => 'O dia de vencimento deve estar entre 1 e 31.']],
            [['payment_plan' => $this->paymentPlan(['num_installments' => 1000])], ['payment_plan.num_installments']],
            [['payment_plan' => $this->paymentPlan(['installments' => [['installment_number' => 1, 'due_date' => 'abc']]])], ['payment_plan.installments.0.due_date']],
            [['post_event_consumption_report' => 'data:image/webp;base64,AAAA'], ['post_event_consumption_report' => 'O relatório de consumo deve ser PNG, JPG, PDF, DOC ou DOCX.']],
            [['date' => 'abc', 'event_place_id' => null], ['date']],
        ];

        foreach ($cases as [$payload, $errors]) {
            $this->putJson("/api/events/{$event->id}", $payload)->assertJsonValidationErrors($errors);
        }
    }

    public function test_plan_without_total_is_rejected_instead_of_failing_on_the_not_null_column(): void
    {
        $event = $this->createEvent();

        $this->putJson("/api/events/{$event->id}", ['payment_plan' => $this->paymentPlan(['total_amount' => null])])
            ->assertJsonValidationErrors('payment_plan.total_amount');
    }

    public function test_due_day_past_the_end_of_a_short_month_falls_on_its_last_day(): void
    {
        Carbon::setTestNow('2026-11-27');
        $event = $this->createEvent();

        $this->putJson("/api/events/{$event->id}", ['payment_plan' => $this->paymentPlan(['due_day' => 31])])->assertOk();

        $this->assertSame(
            ['2026-12-31', '2027-01-31', '2027-02-28', '2027-03-31'],
            $event->receivables()->orderBy('due_date')->pluck('due_date')->map(fn ($date) => substr($date, 0, 10))->all()
        );
    }

    public function test_saving_the_event_keeps_the_author_of_existing_notes(): void
    {
        $event = $this->createEvent();
        $author = User::factory()->create();
        $note = EventNote::create(['event_id' => $event->id, 'text' => 'Levar gelo', 'checklist' => false, 'user_id' => $author->id]);

        $this->putJson("/api/events/{$event->id}", ['event_notes' => [
            ['id' => $note->id, 'text' => 'Levar gelo extra', 'checklist' => true, 'user_id' => $this->user->id],
            ['id' => null, 'text' => 'Confirmar horário', 'user_id' => $author->id],
        ]])->assertOk();

        $this->assertSame($author->id, $note->fresh()->user_id);
        $this->assertSame('Levar gelo extra', $note->fresh()->text);
        $this->assertSame($this->user->id, $event->eventNotes()->where('text', 'Confirmar horário')->value('user_id'));
    }

    public function test_batch_id_from_another_event_is_not_taken_over(): void
    {
        $other = $this->createEvent();
        $otherBatch = $other->eventBatches()->create(['guests' => 50, 'unit_value' => 10]);
        $event = $this->createEvent();

        $this->putJson("/api/events/{$event->id}", ['event_batches' => [['id' => $otherBatch->id, 'guests' => 80, 'unit_value' => 20]]])->assertOk();

        $this->assertSame($other->id, $otherBatch->fresh()->event_id);
        $this->assertSame(50, (int) $otherBatch->fresh()->guests);
        $this->assertSame(1, $event->eventBatches()->count());
    }

    public function test_failed_store_does_not_leave_a_half_created_event(): void
    {
        $this->user->collaborator()->create(Collaborator::factory()->make()->getAttributes());
        $this->mock(EventHistoryService::class)->shouldReceive('logEventCreation')->andThrow(new LogicException('falhou no fim'));
        $this->withoutExceptionHandling();

        try {
            $this->postJson('/api/events', [
                'name' => 'Formatura',
                'event_status_id' => EventStatus::create(['name' => 'Pré-Reserva'])->id,
                'event_batches' => [['guests' => 100, 'unit_value' => 50], ['guests' => null, 'unit_value' => null]],
            ]);
            $this->fail('O store deveria ter falhado.');
        } catch (LogicException) {
        }

        $this->assertSame(0, Event::count());
    }

    private function createEvent(): Event
    {
        return Event::create(['name' => 'Casamento', 'event_status_id' => EventStatus::firstOrCreate(['name' => 'Pré-Reserva'])->id])->refresh();
    }

    private function paymentPlan(array $overrides = []): array
    {
        return array_merge([
            'total_amount' => 4000,
            'down_payment' => 0,
            'num_installments' => 4,
            'installment_amount' => 1000,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'due_day' => 12,
        ], $overrides);
    }
}
