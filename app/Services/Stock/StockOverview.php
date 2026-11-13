<?php

namespace App\Services\Stock;

use App\Models\Event;
use App\Models\EventStock;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StockOverview
{
    public const UPCOMING_STATUSES = ['Contrato', 'Fechado', 'Planejamento'];

    public function __construct(
        private StockLedger $ledger,
        private EventStockForecast $forecast,
        private EventStockService $eventStock,
    ) {
    }

    public static function stockProducts(): Builder
    {
        return Product::query()->where(function (Builder $query) {
            $query->where('composition_item', true)->orWhereDoesntHave('components');
        });
    }

    public function products(): Collection
    {
        $products = self::stockProducts()->with(['unit', 'productGroup', 'stockLimit'])->orderBy('name')->get();
        $ids = $products->pluck('id')->all();
        $balances = $this->ledger->balances($ids);
        $averages = $this->ledger->averagePrices($ids);
        $lastPurchases = $this->ledger->lastPurchases($ids);

        return $products->map(function (Product $product) use ($balances, $averages, $lastPurchases) {
            $balance = $balances[$product->id] ?? 0.0;
            $average = $averages[$product->id] ?? null;
            $min = $product->stockLimit?->min_quantity;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'group_id' => $product->product_group_id,
                'group' => $product->productGroup?->description ?? 'Sem grupo',
                'unit' => $product->unit?->description,
                'volume' => $product->volume,
                'consumption_per_guest' => $product->consumption_per_guest,
                'balance' => $balance,
                'average_price' => $average,
                'total_value' => $average !== null && $balance > 0 ? round($balance * $average, 2) : null,
                'last_price' => $lastPurchases[$product->id]['unit_price'] ?? null,
                'last_purchased_at' => $lastPurchases[$product->id]['purchased_at'] ?? null,
                'min_quantity' => $min,
                'max_quantity' => $product->stockLimit?->max_quantity,
                'below_min' => $min !== null && $balance < $min,
            ];
        });
    }

    public function events(Carbon $start, Carbon $end): Collection
    {
        $events = Event::query()
            ->with(['eventStatus', 'eventStock.items', ...EventStockForecast::RELATIONS])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function (Builder $query) {
                $query->whereHas('eventStock')
                    ->orWhereHas('eventStatus', fn (Builder $status) => $status->where('name', EventStockService::SEND_STATUS));
            })
            ->orderBy('date')
            ->get();

        $averages = $this->ledger->averagePrices();

        return $events->map(function (Event $event) use ($averages) {
            $stock = $event->eventStock;
            $forecast = $this->forecast->items($event);

            return [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date?->format('Y-m-d'),
                'status' => $event->eventStatus?->name,
                'guests' => $stock?->guests ?? $event->totalGuests(),
                'state' => $this->state($stock),
                'forecast_items' => $forecast->count(),
                'missing_items' => $forecast->filter(fn ($item) => $item['missing'])->count(),
                'sent_items' => $stock?->items->where('sent_quantity', '>', 0)->count() ?? 0,
                'sent_at' => $stock?->sent_at,
                'returned_at' => $stock?->returned_at,
                'return_available' => $stock && ! $stock->isReturned() && $this->eventStock->returnBlockedReason($event) === null,
                'consumed_value' => $stock?->isReturned() ? $this->consumedValue($stock, $averages) : null,
            ];
        });
    }

    public function pending(): Collection
    {
        $events = Event::query()
            ->with(['eventStatus', ...EventStockForecast::RELATIONS])
            ->whereDate('date', '>=', today())
            ->whereHas('eventStatus', fn (Builder $status) => $status->whereIn('name', self::UPCOMING_STATUSES))
            ->orderBy('date')
            ->get();

        $products = [];

        foreach ($events as $event) {
            foreach ($this->forecast->items($event) as $item) {
                if (! $item['missing']) {
                    continue;
                }

                $products[$item['product_id']] ??= [...collect($item)->only(['product_id', 'name', 'group', 'unit', 'consumption_per_guest', 'volume', 'missing'])->all(), 'events' => []];
                $products[$item['product_id']]['events'][] = [
                    'id' => $event->id,
                    'name' => $event->name,
                    'date' => $event->date?->format('Y-m-d'),
                    'status' => $event->eventStatus?->name,
                ];
            }
        }

        return collect($products)->sortBy([['group', 'asc'], ['name', 'asc']])->values();
    }

    public function consumedValue(EventStock $stock, array $averages): float
    {
        return round($stock->items->sum(
            fn ($item) => ($item->consumedQuantity($stock->isReturned()) ?? 0) * ($averages[$item->product_id] ?? 0)
        ), 2);
    }

    public function state(?EventStock $stock): string
    {
        return match (true) {
            ! $stock => 'forecast',
            $stock->isReturned() => 'returned',
            $stock->return_saved_at !== null => 'return_draft',
            default => 'sent',
        };
    }
}
