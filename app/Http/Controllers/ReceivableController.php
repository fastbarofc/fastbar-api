<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReceivableRequest;
use App\Http\Requests\UpdateReceivableRequest;
use App\Http\Resources\ReceivableResource;
use App\Models\Event;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Financeiro > Contas a receber: parcelas dos eventos (contrato e aditivos).
 * Situações: vence hoje, vencida (cobrar a partir do dia seguinte), a vencer e paga.
 */
class ReceivableController extends Controller
{
    private const RELATIONS = ['paymentMethod', 'parthenonBankAccount', 'settledBy', 'receivableable'];

    public function index(Request $request)
    {
        $base = $this->filteredQuery($request);

        $query = (clone $base)->with(self::RELATIONS);
        $this->applySituation($query, $request->input('situation', 'open'));

        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        $sortable = ['id', 'due_date', 'amount', 'description', 'paid_at'];
        $orderBy = in_array($request->input('order_by'), $sortable, true) ? $request->input('order_by') : 'due_date';
        $query->orderBy($orderBy, $direction)->orderBy('id');

        $page = $query->paginate((int) $request->input('per_page', 30));
        $this->loadEventDetails($page->getCollection());

        return response()->json([
            'data' => ReceivableResource::collection($page->getCollection()),
            'pagination' => [
                'page' => $page->currentPage(),
                'total_pages' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'summary' => $this->summary($base),
        ]);
    }

    public function store(StoreReceivableRequest $request)
    {
        $receivable = Receivable::create($request->validated());
        return response()->json(new ReceivableResource($receivable), 201);
    }

    public function show(Receivable $receivable)
    {
        $receivable->load(self::RELATIONS);
        $this->loadEventDetails(collect([$receivable]));
        return response()->json(new ReceivableResource($receivable));
    }

    public function update(UpdateReceivableRequest $request, Receivable $receivable)
    {
        $receivable->update($request->validated());
        return response()->json(new ReceivableResource($receivable));
    }

    public function destroy(Receivable $receivable)
    {
        $receivable->delete();
        return response()->json(['message' => 'Receivable deleted successfully'], 200);
    }

    /** Dar baixa: registra o pagamento da parcela. */
    public function settle(Request $request, Receivable $receivable)
    {
        $data = $request->validate([
            'paid_at' => 'required|date',
            'received_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'parthenon_bank_account_id' => 'nullable|exists:parthenon_bank_accounts,id',
            'notes' => 'nullable|string',
        ], [
            'paid_at.required' => 'Informe a data do pagamento.',
            'received_amount.required' => 'Informe o valor recebido.',
            'payment_method_id.required' => 'Informe a forma de pagamento.',
        ]);

        $receivable->update($data + [
            'status' => 'paid',
            'settled_by' => $request->user()?->id,
        ]);

        return $this->show($receivable->fresh());
    }

    /** Desfazer a baixa: a parcela volta a ficar em aberto. */
    public function reopen(Receivable $receivable)
    {
        $receivable->update([
            'status' => 'pending',
            'paid_at' => null,
            'received_amount' => null,
            'parthenon_bank_account_id' => null,
            'settled_by' => null,
        ]);

        return $this->show($receivable->fresh());
    }

    /** Busca (descrição, nº da parcela, nº/nome do evento, cliente), evento e período de vencimento. */
    private function filteredQuery(Request $request): Builder
    {
        $query = Receivable::query();

        if ($request->filled('event_id')) {
            $query->where('receivableable_type', Event::class)->where('receivableable_id', $request->input('event_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('due_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('due_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->input('payment_method_id'));
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $digits = ctype_digit($search) ? (int) $search : null;
            $query->where(function (Builder $q) use ($search, $digits) {
                $q->where('description', 'like', "%{$search}%");
                if ($digits !== null) {
                    $q->orWhere('id', $digits)
                        ->orWhere(fn ($w) => $w->where('receivableable_type', Event::class)->where('receivableable_id', $digits));
                }
                $q->orWhereHasMorph('receivableable', [Event::class], function (Builder $e) use ($search) {
                    $e->where('name', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            });
        }

        return $query;
    }

    private function applySituation(Builder $query, string $situation): void
    {
        $today = now()->toDateString();
        $open = fn (Builder $q) => $q->where('status', '!=', 'paid')->whereNull('paid_at');

        match ($situation) {
            'today' => $open($query)->whereDate('due_date', $today),
            'overdue' => $open($query)->whereDate('due_date', '<', $today),
            'upcoming' => $open($query)->whereDate('due_date', '>', $today),
            'paid' => $query->where(fn ($q) => $q->where('status', 'paid')->orWhereNotNull('paid_at')),
            'all' => null,
            default => $open($query),
        };
    }

    /** Totais dos cartões do topo (respeitam a busca e o período, não a aba escolhida). */
    private function summary(Builder $base): array
    {
        $result = [];
        foreach (['today', 'overdue', 'upcoming', 'paid'] as $situation) {
            $q = clone $base;
            $this->applySituation($q, $situation);
            $sumColumn = $situation === 'paid' ? 'COALESCE(received_amount, amount)' : 'amount';
            $result[$situation] = [
                'count' => (clone $q)->count(),
                'amount' => (float) (clone $q)->sum(DB::raw($sumColumn)),
            ];
        }
        return $result;
    }

    /** Evento da parcela com cliente e data, para mostrar na tabela. */
    private function loadEventDetails($receivables): void
    {
        $events = $receivables->pluck('receivableable')->filter(fn ($r) => $r instanceof Event);
        if ($events->isNotEmpty()) {
            (new \Illuminate\Database\Eloquent\Collection($events->values()->all()))->load('client:id,name');
        }
    }
}
