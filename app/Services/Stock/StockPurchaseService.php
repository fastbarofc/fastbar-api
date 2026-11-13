<?php

namespace App\Services\Stock;

use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockPurchaseService
{
    public function __construct(private StockLedger $ledger)
    {
    }

    public function save(?StockPurchase $purchase, array $data, User $user): StockPurchase
    {
        return DB::transaction(function () use ($purchase, $data, $user) {
            $attributes = [
                'purchased_at' => $data['purchased_at'],
                'supplier' => $data['supplier'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($purchase) {
                $purchase->update($attributes);
                $this->deleteItems($purchase);
            } else {
                $purchase = StockPurchase::create([...$attributes, 'user_id' => $user->id]);
            }

            foreach ($data['items'] as $line) {
                $item = $purchase->items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => round((float) $line['quantity'], 2),
                    'unit_price' => isset($line['unit_price']) && $line['unit_price'] !== '' ? round((float) $line['unit_price'], 2) : null,
                ]);

                $this->ledger->post(
                    $item->product_id,
                    StockMovement::TYPE_PURCHASE,
                    $item->quantity,
                    $item,
                    userId: $user->id,
                    unitPrice: $item->unit_price,
                );
            }

            return $purchase->fresh(['items.product.unit', 'user']);
        });
    }

    public function delete(StockPurchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $this->deleteItems($purchase);
            $purchase->delete();
        });
    }

    public function adjust(array $data, User $user): StockAdjustment
    {
        return DB::transaction(function () use ($data, $user) {
            $adjustment = StockAdjustment::create([
                'product_id' => $data['product_id'],
                'quantity' => round((float) $data['quantity'], 2),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $user->id,
            ]);

            $this->ledger->post($adjustment->product_id, StockMovement::TYPE_ADJUSTMENT, $adjustment->quantity, $adjustment, userId: $user->id);

            return $adjustment;
        });
    }

    public function deleteAdjustment(StockAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            $this->ledger->deleteFor($adjustment);
            $adjustment->delete();
        });
    }

    private function deleteItems(StockPurchase $purchase): void
    {
        foreach ($purchase->items()->get() as $item) {
            $this->ledger->deleteFor($item);
            $item->delete();
        }
    }
}
