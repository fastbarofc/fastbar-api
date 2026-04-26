<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;

class PurchaseRequestService
{
    public function syncFulfillmentStatus(PurchaseRequest $purchaseRequest): void
    {
        if (!in_array($purchaseRequest->status, PurchaseRequest::FULFILLMENT_STATUSES)) {
            return;
        }

        $requestProductIds = $purchaseRequest->purchaseRequestProducts()
            ->pluck('product_id')
            ->unique()
            ->values()
            ->toArray();

        if (empty($requestProductIds)) {
            return;
        }

        $coveredProductIds = DB::table('purchase_order_product')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_product.purchase_order_id')
            ->where('purchase_order_product.purchase_request_id', $purchaseRequest->id)
            ->where('purchase_orders.status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->whereNull('purchase_orders.deleted_at')
            ->pluck('purchase_order_product.product_id')
            ->unique()
            ->values()
            ->toArray();

        $coveredCount = count(array_intersect($requestProductIds, $coveredProductIds));
        $totalCount = count($requestProductIds);

        if ($coveredCount === 0) {
            $newStatus = PurchaseRequest::STATUS_CONFIRMED;
        } elseif ($coveredCount >= $totalCount) {
            $newStatus = PurchaseRequest::STATUS_FULFILLED;
        } else {
            $newStatus = PurchaseRequest::STATUS_PARTIALLY_FULFILLED;
        }

        if ($purchaseRequest->status !== $newStatus) {
            $purchaseRequest->update(['status' => $newStatus]);
        }
    }
}
