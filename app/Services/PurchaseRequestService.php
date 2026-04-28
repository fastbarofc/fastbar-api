<?php

namespace App\Services;

use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    public function syncFulfillmentStatus(PurchaseRequest $purchaseRequest): void
    {
        if (!in_array($purchaseRequest->status, PurchaseRequest::FULFILLMENT_STATUSES)) {
            return;
        }

        $items = $purchaseRequest->purchaseRequestProducts()->get(['ordered']);

        if ($items->isEmpty()) {
            return;
        }

        $orderedCount = $items->where('ordered', true)->count();
        $totalCount = $items->count();

        if ($orderedCount === 0) {
            $newStatus = PurchaseRequest::STATUS_CONFIRMED;
        } elseif ($orderedCount >= $totalCount) {
            $newStatus = PurchaseRequest::STATUS_FULFILLED;
        } else {
            $newStatus = PurchaseRequest::STATUS_PARTIALLY_FULFILLED;
        }

        if ($purchaseRequest->status !== $newStatus) {
            $purchaseRequest->update(['status' => $newStatus]);
        }
    }
}
