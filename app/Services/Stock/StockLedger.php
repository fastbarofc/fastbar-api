<?php

namespace App\Services\Stock;

use App\Models\StockMovement;
use App\Models\StockPurchaseItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockLedger
{
    public function balances(?array $productIds = null): array
    {
        return StockMovement::query()
            ->when($productIds !== null, fn ($query) => $query->whereIn('product_id', $productIds))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as balance')
            ->pluck('balance', 'product_id')
            ->map(fn ($balance) => round((float) $balance, 2))
            ->all();
    }

    // Média ponderada só das compras com valor: retorno de evento e ajuste não mudam o custo.
    public function averagePrices(?array $productIds = null): array
    {
        return StockPurchaseItem::query()
            ->whereNotNull('unit_price')
            ->when($productIds !== null, fn ($query) => $query->whereIn('product_id', $productIds))
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) > 0')
            ->selectRaw('product_id, SUM(quantity * unit_price) / SUM(quantity) as average_price')
            ->pluck('average_price', 'product_id')
            ->map(fn ($price) => round((float) $price, 2))
            ->all();
    }

    public function lastPurchases(?array $productIds = null): array
    {
        return StockPurchaseItem::query()
            ->join('stock_purchases', 'stock_purchases.id', '=', 'stock_purchase_items.stock_purchase_id')
            ->whereNotNull('stock_purchase_items.unit_price')
            ->when($productIds !== null, fn ($query) => $query->whereIn('stock_purchase_items.product_id', $productIds))
            ->orderByDesc('stock_purchases.purchased_at')
            ->orderByDesc('stock_purchase_items.id')
            ->get(['stock_purchase_items.product_id', 'stock_purchase_items.unit_price', 'stock_purchases.purchased_at'])
            ->unique('product_id')
            ->mapWithKeys(fn ($item) => [$item->product_id => [
                'unit_price' => (float) $item->unit_price,
                'purchased_at' => substr((string) $item->purchased_at, 0, 10),
            ]])
            ->all();
    }

    public function post(
        int $productId,
        string $type,
        float $quantity,
        ?Model $source = null,
        ?int $eventId = null,
        ?int $userId = null,
        ?float $unitPrice = null,
    ): StockMovement {
        return StockMovement::create([
            'product_id' => $productId,
            'type' => $type,
            'quantity' => round($quantity, 2),
            'unit_price' => $unitPrice,
            'movementable_type' => $source?->getMorphClass(),
            'movementable_id' => $source?->getKey(),
            'event_id' => $eventId,
            'user_id' => $userId,
        ]);
    }

    public function deleteFor(Model $source): void
    {
        DB::table('stock_movements')
            ->where('movementable_type', $source->getMorphClass())
            ->where('movementable_id', $source->getKey())
            ->delete();
    }
}
