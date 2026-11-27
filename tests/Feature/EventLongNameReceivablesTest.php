<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\EventHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EventLongNameReceivablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_event_with_the_longest_name_saves_its_installments(): void
    {
        $name = str_repeat('A', 191);
        $event = $this->createEvent(['name' => $name]);

        $this->putJson("/api/events/{$event->id}", ['payment_plan' => $this->paymentPlan()])->assertOk();

        $this->assertSame(
            ["Parcela 1/4 - {$name}", "Parcela 2/4 - {$name}", "Parcela 3/4 - {$name}", "Parcela 4/4 - {$name}"],
            $event->receivables()->orderBy('id')->pluck('description')->all()
        );
    }

    public function test_name_longer_than_the_column_is_rejected(): void
    {
        $event = $this->createEvent();

        $this->putJson("/api/events/{$event->id}", ['name' => str_repeat('A', 192)])
            ->assertJsonValidationErrors(['name' => 'O campo nome deve ter no máximo 191 caracteres.']);

        $this->postJson('/api/events', ['name' => str_repeat('A', 192)])
            ->assertJsonValidationErrors(['name' => 'O campo nome deve ter no máximo 191 caracteres.']);
    }

    public function test_failed_update_keeps_the_previous_installments(): void
    {
        $this->mock(EventHistoryService::class)
            ->shouldReceive('logEventUpdate')
            ->andReturnUsing(
                fn () => null,
                fn () => throw new LogicException('falha depois de regravar as parcelas'),
            );

        $event = $this->createEvent();
        $this->putJson("/api/events/{$event->id}", ['payment_plan' => $this->paymentPlan()])->assertOk();
        $receivableIds = $event->receivables()->pluck('id')->all();
        $installmentIds = $event->paymentPlan->installments()->pluck('id')->all();

        $this->withoutExceptionHandling();

        try {
            $this->putJson("/api/events/{$event->id}", [
                'name' => 'Outro nome',
                'payment_plan' => $this->paymentPlan(['num_installments' => 2, 'installment_amount' => 2000]),
            ]);
            $this->fail('O update deveria ter falhado.');
        } catch (LogicException $e) {
            $this->assertSame('falha depois de regravar as parcelas', $e->getMessage());
        }

        $event->refresh();
        $this->assertSame('Casamento', $event->name);
        $this->assertSame(4, $event->paymentPlan->num_installments);
        $this->assertSame($receivableIds, $event->receivables()->pluck('id')->all());
        $this->assertSame($installmentIds, $event->paymentPlan->installments()->pluck('id')->all());
    }

    private function createEvent(array $attributes = []): Event
    {
        return Event::create($attributes + [
            'name' => 'Casamento',
            'client_id' => Client::factory()->create()->id,
            'event_status_id' => EventStatus::create(['name' => 'Pré-Reserva'])->id,
        ])->refresh();
    }

    private function paymentPlan(array $overrides = []): array
    {
        return $overrides + [
            'total_amount' => 4000,
            'down_payment' => 0,
            'num_installments' => 4,
            'installment_amount' => 1000,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'due_day' => 12,
        ];
    }
}
