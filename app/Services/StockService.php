<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\StockMovement;

class StockService
{
    public function registerPurchaseOrderMovements(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_RECEIVED) {
            return;
        }

        $purchaseOrder->load('purchaseOrderProducts.product');

        foreach ($purchaseOrder->purchaseOrderProducts as $orderProduct) {
            StockMovement::create([
                'product_id' => $orderProduct->product_id,
                'department_id' => $purchaseOrder->department_id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $orderProduct->quantity,
                'price' => $orderProduct->price,
                'movementable_type' => PurchaseOrder::class,
                'movementable_id' => $purchaseOrder->id,
            ]);
        }
    }

    public function shouldRegisterMovements(PurchaseOrder $purchaseOrder, ?string $oldStatus): bool
    {
        if (!$purchaseOrder->department_id) {
            return false;
        }

        $isNowReceived = $purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED;
        $wasNotReceived = $oldStatus !== PurchaseOrder::STATUS_RECEIVED;

        return $isNowReceived && $wasNotReceived;
    }
}

