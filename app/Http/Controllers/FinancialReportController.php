<?php

namespace App\Http\Controllers;

use App\Models\BankStatementLine;
use App\Models\CardInvoiceLine;
use App\Models\ArmReportNote;
use App\Models\Contract;
use App\Models\DreRelease;
use App\Models\Event;
use App\Models\Payable;
use App\Models\Receivable;
use App\Services\DreService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Relatórios > DRE e ARM mensal (formato do sistema da MAV). */
class FinancialReportController extends Controller
{
    private const REGIMES = ['competencia', 'vencimento', 'caixa'];

    /** DRE do mês (ou de um período de/até). */
    public function dre(Request $request)
    {
        $regime = in_array($request->input('regime'), self::REGIMES, true) ? $request->input('regime') : 'competencia';
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->format('Y-m-d');
            $to = Carbon::parse($request->input('to'))->format('Y-m-d');
        } else {
            $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01');
            $from = $start->format('Y-m-d');
            $to = $start->copy()->endOfMonth()->format('Y-m-d');
        }

        $filters = array_filter([
            'event_id' => $request->input('event_id'),
            'cost_center_id' => $request->input('cost_center_id'),
        ]);

        return response()->json((new DreService())->dre($from, $to, $regime, $filters));
    }

    /** ARM mensal: demonstrativo de janeiro a dezembro do ano. */
    public function arm(Request $request)
    {
        $regime = in_array($request->input('regime'), self::REGIMES, true) ? $request->input('regime') : 'competencia';
        $year = (int) $request->input('year', now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }
        return response()->json((new DreService())->arm($year, $regime));
    }

    /**
     * Seções que passam de um mês para o outro (tabelas do balanço, dívida, consórcio e a meta):
     * sem valor no mês, vale o último mês salvo antes dele.
     */
    private const ARM_CARRY = ['balanco_dados', 'divida_dados', 'consorcio_dados', 'meta_valor', 'mkt_andamento', 'mkt_nao', 'op_andamento', 'op_nao'];

    /** Textos e tabelas do documento ARM do mês: { secao: texto }. */
    public function armNotes(Request $request)
    {
        [$year, $month] = $this->yearMonth($request);
        $notes = ArmReportNote::where('year', $year)->where('month', $month)->pluck('body', 'section')->all();
        foreach (self::ARM_CARRY as $section) {
            if (isset($notes[$section]) && $notes[$section] !== '') {
                continue;
            }
            $previous = ArmReportNote::where('section', $section)
                ->where(fn ($q) => $q->where('year', '<', $year)->orWhere(fn ($q) => $q->where('year', $year)->where('month', '<', $month)))
                ->where('body', '!=', '')
                ->orderByDesc('year')->orderByDesc('month')
                ->value('body');
            if ($previous !== null) {
                $notes[$section] = $previous;
            }
        }
        return response()->json((object) $notes);
    }

    /**
     * Estoque de bebidas para o ARM: por produto, a quantidade no fim do mês do documento e no fim do mês anterior,
     * o valor unitário (custo médio do estoque, ou o valor do produto) e a categoria (grupo do produto).
     * A quantidade numa data = saldo atual − entradas depois da data + saídas depois da data.
     */
    public function armStock(Request $request)
    {
        [$year, $month] = $this->yearMonth($request);
        $end = Carbon::create($year, $month, 1)->endOfMonth();
        $prevEnd = Carbon::create($year, $month, 1)->subDay()->endOfDay();

        $levels = \App\Models\StockLevel::query()
            ->selectRaw('product_id, SUM(current_quantity) as qty, SUM(total_value) as total_value')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
        $after = fn (Carbon $date) => \App\Models\StockMovement::query()
            ->where('created_at', '>', $date)
            ->selectRaw("product_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as net")
            ->groupBy('product_id')
            ->pluck('net', 'product_id');
        $afterEnd = $after($end);
        $afterPrev = $after($prevEnd);

        $products = \App\Models\Product::query()
            ->with('productGroup:id,name')
            ->whereIn('id', $levels->keys())
            ->get(['id', 'name', 'value', 'product_group_id']);

        $rows = [];
        foreach ($products as $p) {
            $level = $levels[$p->id];
            $qtyNow = (float) $level->qty;
            $unit = $qtyNow > 0 && (float) $level->total_value > 0 ? (float) $level->total_value / $qtyNow : (float) $p->value;
            $cur = $qtyNow - (float) ($afterEnd[$p->id] ?? 0);
            $prev = $qtyNow - (float) ($afterPrev[$p->id] ?? 0);
            $rows[] = [
                'name' => $p->name,
                'category' => $p->productGroup?->name ?? 'Outros',
                'unit' => round($unit, 2),
                'qty' => round($cur, 3),
                'prev_qty' => round($prev, 3),
                'value' => round($cur * $unit, 2),
                'prev_value' => round($prev * $unit, 2),
            ];
        }
        usort($rows, fn ($a, $b) => $b['value'] <=> $a['value']);

        return response()->json([
            'date' => $end->format('Y-m-d'),
            'prev_date' => $prevEnd->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    /** Comissões do ano por pessoa e mês (KPI de comissão do ARM): conta Comissão ou categoria BV. */
    public function armCommission(Request $request)
    {
        [$year] = $this->yearMonth($request);
        $accountIds = \App\Models\FinancialOperation::query()
            ->where('code', \App\Services\DreService::CATEGORY_ACCOUNT['bv'])
            ->pluck('id');
        $payables = Payable::query()
            ->with(['event:id,date', 'partner:id,name', 'collaborator:id,name', 'supplier'])
            ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->where(fn ($q) => $q->where('category', 'bv')->orWhereIn('financial_operation_id', $accountIds))
            ->get();

        $rows = [];
        foreach ($payables as $p) {
            $date = $p->event?->date ?: $p->due_date;
            if (!$date || (int) $date->format('Y') !== $year) {
                continue;
            }
            $m = (int) $date->format('n') - 1;
            if ($p->partner) {
                [$type, $name] = ['BV', $p->partner->name];
            } elseif ($p->collaborator) {
                [$type, $name] = ['INTERNA', $p->collaborator->name];
            } elseif ($p->supplier) {
                [$type, $name] = ['BV', $p->supplier->fantasy_name ?: $p->supplier->corporate_name];
            } else {
                [$type, $name] = ['INTERNA', '(sem nome)'];
            }
            $key = $type . '|' . mb_strtoupper($name);
            $rows[$key] ??= ['type' => $type, 'name' => mb_strtoupper($name), 'values' => array_fill(0, 12, 0.0)];
            $rows[$key]['values'][$m] = round($rows[$key]['values'][$m] + (float) $p->amount, 2);
        }
        $rows = array_values($rows);
        usort($rows, fn ($a, $b) => [$a['type'], $a['name']] <=> [$b['type'], $b['name']]);
        return response()->json(['year' => $year, 'rows' => $rows]);
    }

    /** Salvar o texto (ou a tabela, em JSON) de uma página do documento ARM. */
    public function saveArmNote(Request $request)
    {
        [$year, $month] = $this->yearMonth($request);
        $data = $request->validate([
            'section' => ['required', 'regex:/^[a-z0-9_]{1,40}$/'],
            'body' => 'nullable|string|max:30000',
        ]);
        ArmReportNote::updateOrCreate(
            ['year' => $year, 'month' => $month, 'section' => $data['section']],
            ['body' => $data['body'] ?? '', 'updated_by' => $request->user()?->id]
        );
        return response()->json(['saved' => true]);
    }

    private function yearMonth(Request $request): array
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }
        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }
        return [$year, $month];
    }

    /** Liberações do DRE (todos os meses liberados). */
    public function releases()
    {
        return response()->json(DreRelease::orderBy('month')->get()->map(fn ($r) => $this->releaseJson($r))->values());
    }

    /**
     * Situação do DRE do mês: liberado ou provisório, e os alertas ("Ainda aberto")
     * que deixam o DRE do mês provisório, cada um com a lista dos itens.
     */
    public function dreStatus(Request $request)
    {
        [$month, $from, $to] = $this->monthRange($request->input('month'));
        $release = DreRelease::whereDate('month', $from)->first();
        $alerts = $this->alertsFor($from, $to);

        return response()->json([
            'month' => $month,
            'release' => $release ? $this->releaseJson($release) : null,
            'has_pending' => collect($alerts)->contains(fn ($a) => $a['count'] > 0),
            'alerts' => $alerts,
        ]);
    }

    /** Liberar o DRE do mês. */
    public function release(Request $request)
    {
        [$month, $from, $to] = $this->monthRange($request->input('month'));
        $user = $request->user();
        $pending = collect($this->alertsFor($from, $to))->contains(fn ($a) => $a['count'] > 0);
        $release = DreRelease::whereDate('month', $from)->first() ?? new DreRelease(['month' => $from]);
        $release->fill([
            'released_by' => $user?->id,
            'released_by_name' => $user?->name ?? $user?->email,
            'released_at' => now(),
            'had_pending' => $pending,
        ])->save();

        return response()->json($this->releaseJson($release->fresh()));
    }

    /** Reabrir o DRE do mês (volta a ser provisório). */
    public function reopen(Request $request)
    {
        [, $from] = $this->monthRange($request->input('month'));
        DreRelease::whereDate('month', $from)->delete();
        return response()->json(['reopened' => true]);
    }

    // ---------------------------------------------------------------

    private function monthRange($input): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $input) ? $input : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01');
        return [$month, $start->format('Y-m-d'), $start->copy()->endOfMonth()->format('Y-m-d')];
    }

    private function releaseJson(DreRelease $r): array
    {
        return [
            'month' => substr((string) $r->getRawOriginal('month'), 0, 7),
            'released_by_name' => $r->released_by_name,
            'released_at' => $r->released_at?->toIso8601String(),
            'had_pending' => (bool) $r->had_pending,
        ];
    }

    /** Pendências do mês, no formato do sistema da MAV, adaptadas ao buffet. */
    private function alertsFor(string $from, string $to): array
    {
        $alerts = [];

        // 1) Eventos do mês com contrato assinado e sem nenhuma parcela em Contas a receber
        $eventIds = Event::query()->whereBetween('date', [$from, $to])->pluck('id');
        $signed = Contract::query()->whereIn('event_id', $eventIds)->whereNotNull('signed_at')->pluck('event_id')->unique();
        $withReceivables = Receivable::query()
            ->where('receivableable_type', Event::class)
            ->whereIn('receivableable_id', $signed)
            ->pluck('receivableable_id')->unique();
        $missing = Event::query()->whereIn('id', $signed->diff($withReceivables))->orderBy('date')->get(['id', 'name', 'date']);
        $alerts[] = [
            'key' => 'eventos_sem_parcelas',
            'label' => 'evento(s) com contrato assinado sem parcelas a receber',
            'title' => 'Eventos do mês com contrato assinado e sem parcelas em Contas a receber',
            'link' => '/admin/events',
            'link_label' => 'Abrir eventos',
            'severity' => 'red',
            'count' => $missing->count(),
            'total' => null,
            'items' => $missing->map(fn ($e) => [
                'id' => $e->id,
                'date' => substr((string) $e->date, 0, 10),
                'description' => "Evento {$e->id} · {$e->name}",
                'amount' => null,
                'link' => "/admin/events/{$e->id}",
            ])->values(),
        ];

        // 2) Contas a pagar do mês aguardando aprovação (não entram no DRE até aprovar)
        $waiting = Payable::query()
            ->with('event:id,name,date')
            ->where('approval_status', 'pending')
            ->get()
            ->filter(function ($p) use ($from, $to) {
                $d = substr((string) ($p->event?->date ?: $p->due_date), 0, 10);
                return $d >= $from && $d <= $to;
            })
            ->sortBy('due_date')->values();
        $alerts[] = [
            'key' => 'pagar_aguardando',
            'label' => 'conta(s) a pagar aguardando aprovação',
            'title' => 'Contas a pagar aguardando aprovação (ficam fora do DRE até aprovar)',
            'link' => '/admin/financials/payables',
            'link_label' => 'Abrir contas a pagar',
            'severity' => 'normal',
            'count' => $waiting->count(),
            'total' => round($waiting->sum(fn ($p) => (float) $p->amount), 2),
            'items' => $waiting->map(fn ($p) => [
                'id' => $p->id,
                'date' => substr((string) $p->due_date, 0, 10),
                'description' => $p->description . ($p->event ? " · {$p->event->name}" : ''),
                'amount' => -1 * (float) $p->amount,
            ])->values(),
        ];

        // 3) Movimentos do extrato do banco sem par
        $bank = BankStatementLine::query()->where('status', 'pendente')->whereBetween('txn_date', [$from, $to])->orderBy('txn_date')->get();
        $alerts[] = [
            'key' => 'banco_sem_par',
            'label' => 'movimento(s) do banco sem par',
            'title' => 'Movimentos do banco sem par',
            'link' => '/admin/financials/reconciliation',
            'link_label' => 'Abrir conciliação',
            'severity' => 'normal',
            'count' => $bank->count(),
            'total' => round($bank->sum(fn ($l) => (float) $l->amount), 2),
            'items' => $bank->map(fn ($l) => [
                'id' => $l->id,
                'date' => substr((string) $l->txn_date, 0, 10),
                'description' => $l->description,
                'amount' => (float) $l->amount,
            ])->values(),
        ];

        // 4) Linhas da fatura do cartão sem tratar (pelo vencimento da fatura)
        $card = CardInvoiceLine::query()->where('status', 'pendente')->whereBetween('due_date', [$from, $to])->orderBy('txn_date')->get();
        $alerts[] = [
            'key' => 'cartao_sem_tratar',
            'label' => 'linha(s) da fatura do cartão sem tratar',
            'title' => 'Linhas da fatura do cartão sem tratar',
            'link' => '/admin/financials/credit-card',
            'link_label' => 'Abrir cartão de crédito',
            'severity' => 'normal',
            'count' => $card->count(),
            'total' => round(-1 * $card->sum(fn ($l) => (float) $l->amount), 2),
            'items' => $card->map(fn ($l) => [
                'id' => $l->id,
                'date' => substr((string) ($l->txn_date ?? $l->due_date), 0, 10),
                'description' => $l->description,
                'amount' => -1 * (float) $l->amount,
            ])->values(),
        ];

        // 5) Lançamentos do mês sem classificação no DRE (conta sem lugar na estrutura)
        $unclassified = collect((new DreService())->entries($from, $to, 'competencia'))
            ->filter(fn ($e) => ($e['line']['section'] ?? null) === 'nao_classificado')->values();
        $alerts[] = [
            'key' => 'sem_classificacao',
            'label' => 'lançamento(s) sem classificação no DRE',
            'title' => 'Lançamentos sem lugar na estrutura do DRE (ajuste em Configurações > Plano de contas)',
            'link' => '/admin/settings',
            'link_params' => ['tab' => 'chart-of-accounts'],
            'link_label' => 'Abrir plano de contas',
            'severity' => 'red',
            'count' => $unclassified->count(),
            'total' => round($unclassified->sum(fn ($e) => ($e['kind'] === 'payable' ? -1 : 1) * (float) $e['amount']), 2),
            'items' => $unclassified->map(fn ($e) => [
                'id' => $e['id'],
                'date' => $e['date'],
                'description' => trim(($e['description'] ?? '') . ($e['counterparty'] ? " · {$e['counterparty']}" : '')),
                'amount' => ($e['kind'] === 'payable' ? -1 : 1) * (float) $e['amount'],
            ])->values(),
        ];

        return $alerts;
    }
}
