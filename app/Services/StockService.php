<?php

namespace App\Services;

use App\Models\Devolution;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

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

            $this->updateStockLevel($orderProduct->product_id, $purchaseOrder->department_id);
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

    public function executeRequisition(Requisition $requisition): void
    {
        $requisition->load('requisitionItems.product');

        foreach ($requisition->requisitionItems as $item) {
            $stockLevel = StockLevel::where('product_id', $item->product_id)
                ->where('department_id', $requisition->department_id)
                ->first();

            $price = $stockLevel ? $stockLevel->weighted_average_price : 0;

            // Saída do departamento de origem
            StockMovement::create([
                'product_id'       => $item->product_id,
                'department_id'    => $requisition->department_id,
                'type'             => StockMovement::TYPE_OUT,
                'quantity'         => $item->quantity,
                'price'            => $price,
                'movementable_type' => Requisition::class,
                'movementable_id'  => $requisition->id,
            ]);

            $this->updateStockLevel($item->product_id, $requisition->department_id);

            // Entrada no departamento destino (transferência entre departamentos)
            if ($requisition->destination_department_id) {
                StockMovement::create([
                    'product_id'       => $item->product_id,
                    'department_id'    => $requisition->destination_department_id,
                    'type'             => StockMovement::TYPE_IN,
                    'quantity'         => $item->quantity,
                    'price'            => $price,
                    'movementable_type' => Requisition::class,
                    'movementable_id'  => $requisition->id,
                ]);

                $this->updateStockLevel($item->product_id, $requisition->destination_department_id);
            }
        }
    }

    public function validateRequisitionStock(Requisition $requisition): array
    {
        $requisition->load('requisitionItems.product');
        $insufficient = [];

        foreach ($requisition->requisitionItems as $item) {
            $stockLevel = StockLevel::where('product_id', $item->product_id)
                ->where('department_id', $requisition->department_id)
                ->first();

            $available = $stockLevel ? $stockLevel->current_quantity : 0;

            if ($available < $item->quantity) {
                $insufficient[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? '',
                    'requested' => $item->quantity,
                    'available' => $available,
                ];
            }
        }

        return $insufficient;
    }

    public function registerDevolutionMovements(Devolution $devolution): void
    {
        $devolution->load('devolutionItems.product', 'requisition');

        foreach ($devolution->devolutionItems as $item) {
            if ($item->quantity_returned <= 0) {
                continue;
            }

            $outMovement = StockMovement::where('product_id', $item->product_id)
                ->where('department_id', $devolution->department_id)
                ->where('movementable_type', Requisition::class)
                ->where('movementable_id', $devolution->requisition_id)
                ->where('type', StockMovement::TYPE_OUT)
                ->first();

            $price = $outMovement ? $outMovement->price : 0;

            StockMovement::create([
                'product_id' => $item->product_id,
                'department_id' => $devolution->department_id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $item->quantity_returned,
                'price' => $price,
                'movementable_type' => Devolution::class,
                'movementable_id' => $devolution->id,
            ]);

            $this->updateStockLevel($item->product_id, $devolution->department_id);
        }
    }

    public function updateStockLevel(int $productId, int $departmentId): void
    {
        $result = StockMovement::where('product_id', $productId)
            ->where('department_id', $departmentId)
            ->selectRaw('
                SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as current_quantity,
                CASE
                    WHEN SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) > 0
                    THEN SUM(CASE WHEN type = "in" THEN quantity * price ELSE 0 END) / SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END)
                    ELSE 0
                END as weighted_average_price
            ')
            ->first();

        $currentQuantity = $result->current_quantity ?? 0;
        $weightedAveragePrice = $result->weighted_average_price ?? 0;

        StockLevel::updateOrCreate(
            [
                'product_id' => $productId,
                'department_id' => $departmentId,
            ],
            [
                'current_quantity' => $currentQuantity,
                'weighted_average_price' => $weightedAveragePrice,
                'total_value' => $currentQuantity * $weightedAveragePrice,
            ]
        );
    }

    public function recalculateAllStockLevels(): void
    {
        $combinations = StockMovement::select('product_id', 'department_id')
            ->distinct()
            ->get();

        foreach ($combinations as $combo) {
            $this->updateStockLevel($combo->product_id, $combo->department_id);
        }
    }
}

