<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\ReturnEventStockRequest;
use App\Http\Requests\Stock\SendEventStockRequest;
use App\Models\Event;
use App\Models\EventStock;
use App\Services\EventContractService;
use App\Services\FileService;
use App\Services\Stock\EventStockForecast;
use App\Services\Stock\EventStockService;
use App\Services\Stock\StockAccess;
use App\Services\Stock\StockLedger;
use App\Services\Stock\StockOverview;
use Illuminate\Http\Request;

class EventStockController extends Controller
{
    public function __construct(
        private EventStockForecast $forecast,
        private EventStockService $service,
        private StockLedger $ledger,
        private StockOverview $overview,
    ) {
    }

    public function show(Request $request, Event $event)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW, StockAccess::EVENTS);

        return response()->json($this->payload($request, $event));
    }

    public function send(SendEventStockRequest $request, Event $event)
    {
        StockAccess::authorize($request->user(), StockAccess::EVENTS);

        $this->service->send($event, $request->validated('items'), $request->user());

        return response()->json($this->payload($request, $event->fresh()));
    }

    public function saveReturn(ReturnEventStockRequest $request, Event $event)
    {
        StockAccess::authorize($request->user(), StockAccess::EVENTS);

        $this->service->saveReturn($event, $request->validated('items'), $request->boolean('finalize'), $request->user());

        return response()->json($this->payload($request, $event->fresh()));
    }

    public function pdf(Request $request, Event $event)
    {
        StockAccess::authorize($request->user(), StockAccess::VIEW, StockAccess::EVENTS);

        $event->load(['eventStatus', 'eventPlace', 'eventStock.items.product.unit', 'eventStock.items.product.productGroup', 'eventStock.sender:id,name']);
        abort_unless($event->eventStock, 422, 'Confirme a saída de estoque antes de imprimir a planilha.');

        $stock = $event->eventStock;
        $averages = $this->ledger->averagePrices($stock->items->pluck('product_id')->all());
        $groups = $stock->items
            ->sortBy(fn ($item) => [$item->product?->productGroup?->description ?? 'Sem grupo', $item->product?->name])
            ->groupBy(fn ($item) => $item->product?->productGroup?->description ?? 'Sem grupo');

        $viewContent = view('/pdfs/event-stock-sheet-pdf', [
            'event' => $event,
            'stock' => $stock,
            'groups' => $groups,
            'averages' => $averages,
            'consumedValue' => $stock->isReturned() ? $this->overview->consumedValue($stock, $averages) : null,
            'logo' => EventContractService::brandAssets()['logo'],
        ]);

        try {
            return response()->json(['pdf_url' => FileService::generatePdf($viewContent, 'planilha-estoque-evento-' . $event->id)]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()], 500);
        }
    }

    private function payload(Request $request, Event $event): array
    {
        $event->load(['eventStatus', 'eventStock.items.product.unit', 'eventStock.items.product.productGroup', 'eventStock.sender:id,name', 'eventStock.returnSaver:id,name', 'eventStock.returner:id,name']);
        $forecast = $this->forecast->items($event);
        $stock = $event->eventStock;

        $productIds = $forecast->pluck('product_id')->merge($stock?->items->pluck('product_id') ?? [])->unique()->values()->all();
        $balances = $this->ledger->balances($productIds);
        $averages = $this->ledger->averagePrices($productIds);
        $canEvents = StockAccess::can($request->user(), StockAccess::EVENTS);

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date?->format('Y-m-d'),
                'status' => $event->eventStatus?->name,
                'guests' => $event->totalGuests(),
            ],
            'can' => [
                'send' => $canEvents && $this->service->sendBlockedReason($event) === null,
                'return' => $canEvents && $this->service->returnBlockedReason($event) === null,
                'manage' => $canEvents,
            ],
            'send_blocked_reason' => $this->service->sendBlockedReason($event),
            'return_blocked_reason' => $this->service->returnBlockedReason($event),
            'forecast' => $forecast->map(fn ($item) => [...$item, 'balance' => $balances[$item['product_id']] ?? 0.0])->values(),
            'missing_count' => $forecast->filter(fn ($item) => $item['missing'])->count(),
            'stock' => $stock ? $this->presentStock($stock, $balances, $averages) : null,
        ];
    }

    private function presentStock(EventStock $stock, array $balances, array $averages): array
    {
        $items = $stock->items
            ->map(function ($item) use ($balances, $averages) {
                $consumed = $item->consumedQuantity();
                $average = $averages[$item->product_id] ?? null;

                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'group' => $item->product?->productGroup?->description ?? 'Sem grupo',
                    'unit' => $item->product?->unit?->description,
                    'expected_quantity' => $item->expected_quantity,
                    'sent_quantity' => $item->sent_quantity,
                    'returned_quantity' => $item->returned_quantity,
                    'extra_quantity' => $item->extra_quantity,
                    'consumed_quantity' => $consumed,
                    'average_price' => $average,
                    'consumed_value' => $consumed !== null && $average !== null ? round($consumed * $average, 2) : null,
                    'balance' => $balances[$item->product_id] ?? 0.0,
                ];
            })
            ->sortBy([['group', 'asc'], ['name', 'asc']])
            ->values();

        return [
            'state' => $this->overview->state($stock),
            'guests' => $stock->guests,
            'sent_at' => $stock->sent_at,
            'sent_by' => $stock->sender?->name,
            'return_saved_at' => $stock->return_saved_at,
            'return_saved_by' => $stock->returnSaver?->name,
            'returned_at' => $stock->returned_at,
            'returned_by' => $stock->returner?->name,
            'items' => $items,
            'consumed_value' => $stock->isReturned() ? round($items->sum('consumed_value'), 2) : null,
        ];
    }
}
