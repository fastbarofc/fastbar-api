<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreStockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\Stock\StockAccess;
use App\Services\Stock\StockPurchaseService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockPurchaseService $service)
    {
    }

    public function index(Request $request)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW, StockAccess::PURCHASES);

        $adjustments = StockAdjustment::query()
            ->with(['product.unit', 'user:id,name'])
            ->when($request->product_id, fn ($query, $id) => $query->where('product_id', $id))
            ->when($request->reason, fn ($query, $reason) => $query->where('reason', $reason))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json([
            'data' => collect($adjustments->items())->map(fn (StockAdjustment $adjustment) => $this->present($adjustment)),
            'pagination' => [
                'page' => $adjustments->currentPage(),
                'total_pages' => $adjustments->lastPage(),
                'per_page' => $adjustments->perPage(),
                'total' => $adjustments->total(),
            ],
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        StockAccess::authorize($request->user(), StockAccess::PURCHASES);

        $adjustment = $this->service->adjust($request->validated(), $request->user());

        return response()->json($this->present($adjustment->load(['product.unit', 'user:id,name'])), 201);
    }

    public function destroy(Request $request, StockAdjustment $adjustment)
    {
        StockAccess::authorize($request->user(), StockAccess::PURCHASES);

        $this->service->deleteAdjustment($adjustment);

        return response()->noContent();
    }

    private function present(StockAdjustment $adjustment): array
    {
        return [
            'id' => $adjustment->id,
            'created_at' => $adjustment->created_at,
            'product_id' => $adjustment->product_id,
            'product' => $adjustment->product?->name,
            'unit' => $adjustment->product?->unit?->description,
            'quantity' => $adjustment->quantity,
            'reason' => $adjustment->reason,
            'reason_label' => StockAdjustment::REASONS[$adjustment->reason] ?? $adjustment->reason,
            'notes' => $adjustment->notes,
            'user' => $adjustment->user?->name,
        ];
    }
}
