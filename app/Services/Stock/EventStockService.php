<?php

namespace App\Services\Stock;

use App\Models\Event;
use App\Models\EventStock;
use App\Models\EventStockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventStockService
{
    public const SEND_STATUS = 'Planejamento';

    public function __construct(
        private EventStockForecast $forecast,
        private StockLedger $ledger,
    ) {
    }

    public function sendBlockedReason(Event $event): ?string
    {
        if ($event->eventStock?->isReturned()) {
            return 'O retorno deste evento já foi finalizado. Corrija as quantidades pelo retorno.';
        }

        if ($event->eventStatus?->name !== self::SEND_STATUS) {
            return 'A saída de estoque só pode ser confirmada com o evento em Planejamento.';
        }

        return null;
    }

    public function returnBlockedReason(Event $event): ?string
    {
        if (! $event->eventStock) {
            return 'Confirme a saída de estoque antes de registrar o retorno.';
        }

        if (! $event->date || ! Carbon::parse($event->date)->lt(today())) {
            return 'O retorno fica disponível depois da data do evento.';
        }

        return null;
    }

    public function send(Event $event, array $items, User $user): EventStock
    {
        $event->loadMissing(['eventStatus', 'eventStock']);
        $this->guard($this->sendBlockedReason($event));

        $expected = $this->forecast->items($event)->keyBy('product_id');
        $payload = collect($items)
            ->filter(fn ($item) => (float) $item['sent_quantity'] > 0)
            ->keyBy('product_id');

        return DB::transaction(function () use ($event, $user, $expected, $payload) {
            $stock = $event->eventStock ?? EventStock::create([
                'event_id' => $event->id,
                'guests' => $event->totalGuests(),
                'sent_at' => now(),
                'sent_by' => $user->id,
            ]);

            $stock->update(['guests' => $event->totalGuests()]);

            foreach ($stock->items()->get() as $item) {
                if (! $payload->has($item->product_id)) {
                    $item->sent_quantity = 0;
                    $this->syncItem($stock, $item, $user);
                    $item->delete();
                }
            }

            foreach ($payload as $productId => $line) {
                $item = $stock->items()->updateOrCreate(
                    ['product_id' => $productId],
                    [
                        'expected_quantity' => $expected->get($productId)['expected_quantity'] ?? null,
                        'sent_quantity' => round((float) $line['sent_quantity'], 2),
                    ]
                );

                $this->syncItem($stock, $item, $user);
            }

            return $stock->fresh('items');
        });
    }

    public function saveReturn(Event $event, array $items, bool $finalize, User $user): EventStock
    {
        $event->loadMissing('eventStock');
        $this->guard($this->returnBlockedReason($event));

        $stock = $event->eventStock;
        $existing = $stock->items()->get()->keyBy('product_id');
        $errors = [];

        foreach (array_values($items) as $index => $line) {
            $sent = $existing->get($line['product_id'])?->sent_quantity ?? 0;

            if (isset($line['returned_quantity']) && (float) $line['returned_quantity'] > $sent) {
                $errors["items.$index.returned_quantity"] = 'O retorno não pode ser maior que a quantidade enviada.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($stock, $items, $existing, $finalize, $user) {
            $stock->fill(['return_saved_at' => now(), 'return_saved_by' => $user->id]);

            if ($finalize && ! $stock->isReturned()) {
                $stock->fill(['returned_at' => now(), 'returned_by' => $user->id]);
            }

            $stock->save();

            foreach ($items as $line) {
                $item = $existing->get($line['product_id'])
                    ?? $stock->items()->make(['product_id' => $line['product_id'], 'sent_quantity' => 0]);

                $item->fill([
                    'returned_quantity' => $this->nullableQuantity($line['returned_quantity'] ?? null),
                    'extra_quantity' => $this->nullableQuantity($line['extra_quantity'] ?? null),
                ]);

                if (! $item->exists && ! $item->extra_quantity) {
                    continue;
                }

                $item->save();
            }

            foreach ($stock->items()->get() as $item) {
                $this->syncItem($stock, $item, $user);
            }

            return $stock->fresh('items');
        });
    }

    // Lança só a diferença entre o que o item pede e o que já foi movimentado, para cada tipo.
    private function syncItem(EventStock $stock, EventStockItem $item, User $user): void
    {
        $returned = $stock->isReturned();
        $targets = [
            StockMovement::TYPE_EVENT_OUT => -$item->sent_quantity,
            StockMovement::TYPE_EVENT_RETURN => $returned ? (float) $item->returned_quantity : 0,
            StockMovement::TYPE_EVENT_EXTRA => $returned ? -(float) $item->extra_quantity : 0,
        ];

        $posted = $item->movements()
            ->groupBy('type')
            ->selectRaw('type, SUM(quantity) as total')
            ->pluck('total', 'type');

        foreach ($targets as $type => $target) {
            $delta = round($target - (float) ($posted[$type] ?? 0), 2);

            if (abs($delta) >= 0.01) {
                $this->ledger->post($item->product_id, $type, $delta, $item, $stock->event_id, $user->id);
            }
        }
    }

    private function nullableQuantity(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : round((float) $value, 2);
    }

    private function guard(?string $reason): void
    {
        if ($reason) {
            abort(422, $reason);
        }
    }
}
