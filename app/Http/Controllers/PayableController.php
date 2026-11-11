<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayableRequest;
use App\Http\Requests\UpdatePayableRequest;
use App\Http\Resources\PayableResource;
use App\Models\Event;
use App\Models\Payable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Financeiro > Contas a pagar e BV dos parceiros.
 * Um BV nasce como ordem de pagamento "aguardando aprovação"; aprovado, vira conta a pagar.
 */
class PayableController extends Controller
{
    private const RELATIONS = ['financialOperation', 'event.client:id,name', 'costCenter', 'supplier', 'partner', 'collaborator', 'paymentMethod', 'parthenonBankAccount', 'cardAccount', 'approvedBy'];

    public function index(Request $request)
    {
        $base = $this->filteredQuery($request);

        $query = (clone $base)->with(self::RELATIONS);
        $this->applySituation($query, $request->input('situation', 'all'));

        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        $sortable = ['id', 'due_date', 'amount', 'description', 'paid_at', 'created_at'];
        $orderBy = in_array($request->input('order_by'), $sortable, true) ? $request->input('order_by') : 'due_date';
        $page = $query->orderBy($orderBy, $direction)->orderBy('id')->paginate((int) $request->input('per_page', 30));

        return response()->json([
            'data' => PayableResource::collection($page->getCollection()),
            'pagination' => [
                'page' => $page->currentPage(),
                'total_pages' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'summary' => $this->summary($base),
        ]);
    }

    public function store(StorePayableRequest $request)
    {
        $data = $request->validated();
        $data['approval_status'] = $data['approval_status'] ?? 'approved';
        if ($data['approval_status'] === 'approved') {
            $data['approved_by'] = $request->user()?->id;
            $data['approved_at'] = now();
        }
        $payable = Payable::create($data);
        return response()->json(new PayableResource($payable->fresh(self::RELATIONS)), 201);
    }

    public function show(Payable $payable)
    {
        return response()->json(new PayableResource($payable->load(self::RELATIONS)));
    }

    public function update(UpdatePayableRequest $request, Payable $payable)
    {
        $payable->update($request->validated());
        return response()->json(new PayableResource($payable->fresh(self::RELATIONS)));
    }

    public function destroy(Payable $payable)
    {
        $payable->delete();
        return response()->json(['message' => 'Payable deleted successfully'], 200);
    }

    /** Aprovar a ordem de pagamento (BV): vira conta a pagar. */
    public function approve(Request $request, Payable $payable)
    {
        $payable->update([
            'approval_status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);
        return $this->show($payable->fresh());
    }

    /** Recusar a ordem de pagamento. */
    public function reject(Request $request, Payable $payable)
    {
        $data = $request->validate(['notes' => 'nullable|string']);
        $payable->update([
            'approval_status' => 'rejected',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
            'notes' => $data['notes'] ?? $payable->notes,
        ]);
        return $this->show($payable->fresh());
    }

    /** Dar baixa: registra o pagamento. */
    public function settle(Request $request, Payable $payable)
    {
        if ($payable->approval_status !== 'approved') {
            return response()->json(['message' => 'Esta conta ainda não foi aprovada.'], 422);
        }
        $data = $request->validate([
            'paid_at' => 'required|date',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'parthenon_bank_account_id' => 'nullable|exists:parthenon_bank_accounts,id',
            'notes' => 'nullable|string',
        ], [
            'paid_at.required' => 'Informe a data do pagamento.',
            'paid_amount.required' => 'Informe o valor pago.',
        ]);
        $payable->update($data + ['status' => 'paid']);
        return $this->show($payable->fresh());
    }

    /** Desfazer a baixa. */
    public function reopen(Payable $payable)
    {
        $payable->update(['status' => 'pending', 'paid_at' => null, 'paid_amount' => null]);
        return $this->show($payable->fresh());
    }

    /**
     * Sugestão para a ordem de BV de um evento: parceiro do evento, valor do contrato
     * e vencimento (data da 1ª parcela do cliente; sem parcelas, a data do evento).
     */
    public function bvSuggestion(Event $event)
    {
        $firstDue = $event->receivables()->orderBy('due_date')->value('due_date');
        $existing = Payable::where('event_id', $event->id)->where('category', 'bv')->where('approval_status', '!=', 'rejected')->sum('amount');

        return response()->json([
            'event_id' => $event->id,
            'partner_id' => $event->partner_id,
            'total_value' => (float) ($event->total_value ?: $event->sub_total ?: 0),
            'due_date' => $firstDue ? \Carbon\Carbon::parse($firstDue)->format('Y-m-d') : optional($event->date)->format('Y-m-d'),
            'existing_bv' => (float) $existing,
        ]);
    }

    /**
     * Acerto do evento: orçado (valor do contrato + aditivos, custos lançados) x realizado (recebido, pago).
     */
    public function eventSettlement(Event $event)
    {
        $event->load('client:id,name');
        $receivables = $event->receivables()->with('paymentMethod')->orderBy('due_date')->get();
        $payables = Payable::with(['supplier', 'partner', 'collaborator', 'costCenter'])
            ->where('event_id', $event->id)
            ->where('approval_status', '!=', 'rejected')
            ->orderBy('due_date')
            ->get();

        $isPaid = fn ($x) => $x->status === 'paid' || $x->paid_at;
        $contract = (float) ($event->total_value ?: $event->sub_total ?: 0);
        $billed = (float) $receivables->sum('amount');
        $received = (float) $receivables->filter($isPaid)->sum(fn ($r) => $r->received_amount ?? $r->amount);

        $approved = $payables->where('approval_status', 'approved');
        $costPlanned = (float) $approved->sum('amount');
        $costPaid = (float) $approved->filter($isPaid)->sum(fn ($p) => $p->paid_amount ?? $p->amount);

        $categories = $approved->groupBy(fn ($p) => $p->category ?: 'outro')->map(fn ($group, $key) => [
            'category' => $key,
            'planned' => (float) $group->sum('amount'),
            'paid' => (float) $group->filter($isPaid)->sum(fn ($p) => $p->paid_amount ?? $p->amount),
            'count' => $group->count(),
        ])->values();

        $resultPlanned = $contract - $costPlanned;
        $resultReal = $received - $costPaid;

        return response()->json([
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => optional($event->date)->format('Y-m-d'),
                'client' => $event->client?->name,
                'guests' => $event->event_guests ?? null,
                'present_guests' => $event->present_guests ?? null,
            ],
            'revenue' => [
                'contract' => $contract,
                'billed' => $billed,
                'received' => $received,
                'open' => round($billed - $received, 2),
                'not_billed' => round($contract - $billed, 2),
            ],
            'costs' => [
                'planned' => $costPlanned,
                'paid' => $costPaid,
                'open' => round($costPlanned - $costPaid, 2),
                'awaiting_count' => $payables->where('approval_status', 'pending')->count(),
                'awaiting_amount' => (float) $payables->where('approval_status', 'pending')->sum('amount'),
                'by_category' => $categories,
            ],
            'result' => [
                'planned' => round($resultPlanned, 2),
                'planned_margin' => $contract > 0 ? round($resultPlanned / $contract * 100, 1) : null,
                'real' => round($resultReal, 2),
                'real_margin' => $received > 0 ? round($resultReal / $received * 100, 1) : null,
            ],
            'receivables' => \App\Http\Resources\ReceivableResource::collection($receivables),
            'payables' => PayableResource::collection($payables),
        ]);
    }

    /**
     * Relatório do contador: tudo o que foi recebido e pago no mês (pela data da baixa).
     */
    public function monthlyReport(Request $request)
    {
        $request->validate(['month' => ['required', 'regex:/^\d{4}-\d{2}$/']]);
        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('month') . '-01')->startOfDay();
        $end = (clone $start)->endOfMonth();

        $paidIn = fn ($q) => $q->whereNotNull('paid_at')->whereBetween('paid_at', [$start, $end]);

        $receivables = $paidIn(\App\Models\Receivable::with(['paymentMethod', 'parthenonBankAccount', 'receivableable']))
            ->orderBy('paid_at')->get();
        $events = $receivables->pluck('receivableable')->filter(fn ($e) => $e instanceof Event);
        if ($events->isNotEmpty()) {
            (new \Illuminate\Database\Eloquent\Collection($events->values()->all()))->load('client:id,name');
        }
        $payables = $paidIn(Payable::with(['event', 'supplier', 'partner', 'collaborator', 'costCenter', 'paymentMethod', 'parthenonBankAccount', 'financialOperation']))
            ->orderBy('paid_at')->get();

        $inValue = fn ($r) => (float) ($r->received_amount ?? $r->amount);
        $outValue = fn ($p) => (float) ($p->paid_amount ?? $p->amount);
        $group = fn ($items, $key, $value) => $items->groupBy($key)->map(fn ($g, $k) => ['key' => $k ?: '-', 'count' => $g->count(), 'amount' => round($g->sum($value), 2)])->values();

        $totalIn = round($receivables->sum($inValue), 2);
        $totalOut = round($payables->sum($outValue), 2);

        return response()->json([
            'month' => $request->input('month'),
            'totals' => ['in' => $totalIn, 'out' => $totalOut, 'balance' => round($totalIn - $totalOut, 2)],
            'in_by_payment_method' => $group($receivables, fn ($r) => $r->paymentMethod?->name, $inValue),
            'out_by_category' => $group($payables, fn ($p) => $p->category ?: 'outro', $outValue),
            'out_by_cost_center' => $group($payables, fn ($p) => $p->costCenter ? trim($p->costCenter->code . ' - ' . $p->costCenter->description) : null, $outValue),
            'receivables' => \App\Http\Resources\ReceivableResource::collection($receivables),
            'payables' => PayableResource::collection($payables),
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Payable::query();

        foreach (['event_id', 'category', 'approval_status', 'partner_id', 'supplier_id', 'cost_center_id', 'card_account_id', 'financial_operation_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('due_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('due_date', '<=', $request->input('date_to'));
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $digits = ctype_digit($search) ? (int) $search : null;
            $query->where(function (Builder $q) use ($search, $digits) {
                $q->where('description', 'like', "%{$search}%");
                if ($digits !== null) {
                    $q->orWhere('id', $digits)->orWhere('event_id', $digits);
                }
                $q->orWhereHas('event', fn ($e) => $e->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn ($s) => $s->where('fantasy_name', 'like', "%{$search}%")->orWhere('corporate_name', 'like', "%{$search}%"))
                    ->orWhereHas('collaborator', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Situações: awaiting (aguardando aprovação), rejected (recusada), e para as aprovadas
     * open (em aberto), today, overdue, upcoming, paid; all = todas.
     */
    private function applySituation(Builder $query, string $situation): void
    {
        $today = now()->toDateString();
        $open = fn (Builder $q) => $q->where('approval_status', 'approved')->where('status', '!=', 'paid')->whereNull('paid_at');

        match ($situation) {
            'awaiting' => $query->where('approval_status', 'pending'),
            'rejected' => $query->where('approval_status', 'rejected'),
            'open' => $open($query),
            'today' => $open($query)->whereDate('due_date', $today),
            'overdue' => $open($query)->whereDate('due_date', '<', $today),
            'upcoming' => $open($query)->whereDate('due_date', '>', $today),
            'paid' => $query->where(fn ($q) => $q->where('status', 'paid')->orWhereNotNull('paid_at')),
            default => null,
        };
    }

    private function summary(Builder $base): array
    {
        $result = [];
        foreach (['awaiting', 'open', 'today', 'overdue', 'upcoming', 'paid', 'rejected'] as $situation) {
            $q = clone $base;
            $this->applySituation($q, $situation);
            $sumColumn = $situation === 'paid' ? 'COALESCE(paid_amount, amount)' : 'amount';
            $result[$situation] = [
                'count' => (clone $q)->count(),
                'amount' => (float) (clone $q)->sum(DB::raw($sumColumn)),
            ];
        }
        return $result;
    }
}
