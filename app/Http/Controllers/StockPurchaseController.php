<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\SaveStockPurchaseRequest;
use App\Models\StockPurchase;
use App\Models\StockPurchaseItem;
use App\Services\Stock\StockAccess;
use App\Services\Stock\StockPurchaseService;
use Illuminate\Http\Request;

class StockPurchaseController extends Controller
{
    public function __construct(private StockPurchaseService $service)
    {
    }

    public function index(Request $request)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW, StockAccess::PURCHASES);

        $purchases = StockPurchase::query()
            ->select('stock_purchases.*')
            ->addSelect(['total' => StockPurchaseItem::query()
                ->selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
                ->whereColumn('stock_purchase_id', 'stock_purchases.id')])
            ->with(['user:id,name'])
            ->withCount('items')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('supplier', 'like', "%$search%")
                        ->orWhere('invoice_number', 'like', "%$search%")
                        ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', "%$search%"));
                });
            })
            ->when($request->start, fn ($query, $start) => $query->whereDate('purchased_at', '>=', $start))
            ->when($request->end, fn ($query, $end) => $query->whereDate('purchased_at', '<=', $end))
            ->orderByDesc('purchased_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json([
            'data' => collect($purchases->items())->map(fn (StockPurchase $purchase) => $this->summary($purchase)),
            'pagination' => [
                'page' => $purchases->currentPage(),
                'total_pages' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
            ],
        ]);
    }

    public function show(Request $request, StockPurchase $purchase)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW, StockAccess::PURCHASES);

        return response()->json($this->detail($purchase->load(['items.product.unit', 'user:id,name'])));
    }

    public function store(SaveStockPurchaseRequest $request)
    {
        StockAccess::authorize($request->user(), StockAccess::PURCHASES);

        $purchase = $this->service->save(null, $request->validated(), $request->user());

        return response()->json($this->detail($purchase), 201);
    }

    public function update(SaveStockPurchaseRequest $request, StockPurchase $purchase)
    {
        StockAccess::authorize($request->user(), StockAccess::PURCHASES);

        return response()->json($this->detail($this->service->save($purchase, $request->validated(), $request->user())));
    }

    public function destroy(Request $request, StockPurchase $purchase)
    {
        StockAccess::authorize($request->user(), StockAccess::PURCHASES);

        $this->service->delete($purchase);

        return response()->noContent();
    }

    private function summary(StockPurchase $purchase): array
    {
        return [
            'id' => $purchase->id,
            'purchased_at' => $purchase->purchased_at?->format('Y-m-d'),
            'supplier' => $purchase->supplier,
            'invoice_number' => $purchase->invoice_number,
            'notes' => $purchase->notes,
            'items_count' => $purchase->items_count ?? $purchase->items->count(),
            'total' => round((float) ($purchase->total ?? $purchase->items->sum(fn ($item) => $item->quantity * (float) $item->unit_price)), 2),
            'user' => $purchase->user?->name,
            'created_at' => $purchase->created_at,
            'updated_at' => $purchase->updated_at,
        ];
    }

    private function detail(StockPurchase $purchase): array
    {
        return [
            ...$this->summary($purchase),
            'items' => $purchase->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product?->name,
                'unit' => $item->product?->unit?->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->values(),
        ];
    }
}
