<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\UpdateStockLimitsRequest;
use App\Models\StockAdjustment;
use App\Models\StockLimit;
use App\Models\StockMovement;
use App\Models\StockPurchaseItem;
use App\Models\EventStockItem;
use App\Services\Stock\StockAccess;
use App\Services\Stock\StockOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private StockOverview $overview)
    {
    }

    public function products(Request $request)
    {
        StockAccess::authorize($request->user(), ...StockAccess::ALL);

        return response()->json([
            'data' => $this->overview->products()->values(),
            'can' => [
                'purchases' => StockAccess::can($request->user(), StockAccess::PURCHASES),
                'events' => StockAccess::can($request->user(), StockAccess::EVENTS),
                'limits' => StockAccess::can($request->user(), StockAccess::LIMITS),
            ],
            'adjustment_reasons' => collect(StockAdjustment::REASONS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ]);
    }

    public function movements(Request $request)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW);

        $request->validate([
            'product_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:' . implode(',', array_keys(StockMovement::TYPES))],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
        ]);

        $movements = StockMovement::query()
            ->with(['product.unit', 'event:id,name,date', 'user:id,name', 'movementable'])
            ->when($request->product_id, fn ($query, $id) => $query->where('product_id', $id))
            ->when($request->event_id, fn ($query, $id) => $query->where('event_id', $id))
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->start, fn ($query, $start) => $query->whereDate('created_at', '>=', $start))
            ->when($request->end, fn ($query, $end) => $query->whereDate('created_at', '<=', $end))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json([
            'data' => collect($movements->items())->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'created_at' => $movement->created_at,
                'type' => $movement->type,
                'type_label' => StockMovement::TYPES[$movement->type] ?? $movement->type,
                'product' => [
                    'id' => $movement->product_id,
                    'name' => $movement->product?->name,
                    'unit' => $movement->product?->unit?->description,
                ],
                'quantity' => $movement->quantity,
                'unit_price' => $movement->unit_price,
                'event' => $movement->event ? [
                    'id' => $movement->event->id,
                    'name' => $movement->event->name,
                    'date' => $movement->event->date?->format('Y-m-d'),
                ] : null,
                'user' => $movement->user?->name,
                'source' => $this->source($movement),
            ]),
            'pagination' => [
                'page' => $movements->currentPage(),
                'total_pages' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
            'types' => collect(StockMovement::TYPES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
        ]);
    }

    public function events(Request $request)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW, StockAccess::EVENTS);

        $request->validate(['start' => ['nullable', 'date'], 'end' => ['nullable', 'date', 'after_or_equal:start']]);

        $start = $request->start ? Carbon::parse($request->start) : today()->subDays(60);
        $end = $request->end ? Carbon::parse($request->end) : today()->addDays(60);

        return response()->json([
            'data' => $this->overview->events($start, $end),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]);
    }

    public function pending(Request $request)
    {
        StockAccess::authorize($request->user(), ...StockAccess::ALL);

        return response()->json(['data' => $this->overview->pending()]);
    }

    public function updateLimits(UpdateStockLimitsRequest $request)
    {
        StockAccess::authorize($request->user(), StockAccess::LIMITS);

        DB::transaction(function () use ($request) {
            foreach ($request->validated('items') as $item) {
                $min = $item['min_quantity'] ?? null;
                $max = $item['max_quantity'] ?? null;

                if ($min === null && $max === null) {
                    StockLimit::where('product_id', $item['product_id'])->get()->each->delete();
                    continue;
                }

                StockLimit::updateOrCreate(
                    ['product_id' => $item['product_id']],
                    ['min_quantity' => $min, 'max_quantity' => $max, 'user_id' => $request->user()->id]
                );
            }
        });

        return response()->json(['data' => $this->overview->products()->values()]);
    }

    private function source(StockMovement $movement): ?string
    {
        $source = $movement->movementable;

        return match (true) {
            $source instanceof StockPurchaseItem => trim('Compra #' . $source->stock_purchase_id . ($source->purchase?->supplier ? ' · ' . $source->purchase->supplier : '')),
            $source instanceof StockAdjustment => StockAdjustment::REASONS[$source->reason] ?? 'Ajuste',
            $source instanceof EventStockItem, $movement->event_id !== null => $movement->event?->name,
            default => null,
        };
    }
}
