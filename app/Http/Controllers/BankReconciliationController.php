<?php

namespace App\Http\Controllers;

use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Event;
use App\Models\ParthenonBankAccount;
use App\Models\Payable;
use App\Models\Receivable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Financeiro > Conciliação bancária (formato do sistema da MAV).
 *
 * Movimentos do sistema = contas a receber e a pagar já baixadas numa conta do banco/caixa.
 * Pagamento de fatura de cartão aparece como um movimento só (a soma das compras da fatura).
 * O extrato do banco (OFX/CSV) é importado; cada linha é conciliada com um movimento, ignorada
 * ou vira um lançamento novo (tarifa, rendimento...). O saldo do extrato x saldo do sistema diz se a conta está "em dia".
 */
class BankReconciliationController extends Controller
{
    /** Contas que participam (bancos e caixas; cartões ficam na tela Cartão de crédito). */
    private function bankAccounts()
    {
        return ParthenonBankAccount::query()
            ->where(fn ($q) => $q->whereNull('account_kind')->orWhere('account_kind', '!=', 'cartao_credito'))
            ->orderBy('name')->orderBy('bank_name')
            ->get();
    }

    /**
     * Movimentos baixados (todas as datas) de uma ou mais contas.
     * Cada um: ref, account_id, date, amount (+ entrada, − saída), description, counterparty, event.
     */
    private function movements(array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }
        $out = [];

        $receivables = Receivable::query()
            ->with('receivableable')
            ->whereIn('parthenon_bank_account_id', $accountIds)
            ->whereNotNull('paid_at')
            ->get();
        foreach ($receivables as $r) {
            $event = $r->receivableable instanceof Event ? $r->receivableable : null;
            $out[] = [
                'ref' => 'receivable:' . $r->id,
                'kind' => 'receivable',
                'id' => $r->id,
                'account_id' => $r->parthenon_bank_account_id,
                'date' => $r->paid_at->format('Y-m-d'),
                'amount' => round((float) ($r->received_amount ?? $r->amount), 2),
                'description' => $r->description,
                'counterparty' => $event?->client?->name ?? null,
                'event' => $event ? ['id' => $event->id, 'name' => $event->name] : null,
            ];
        }

        $payables = Payable::query()
            ->with(['event:id,name', 'partner:id,name', 'supplier', 'collaborator:id,name', 'cardAccount'])
            ->whereIn('parthenon_bank_account_id', $accountIds)
            ->whereNotNull('paid_at')
            ->get();
        $cardGroups = [];
        foreach ($payables as $p) {
            $amount = round((float) ($p->paid_amount ?? $p->amount), 2);
            if ($p->card_account_id) {
                // Fatura do cartão: um movimento por cartão + vencimento + data do pagamento + conta
                $key = $p->card_account_id . ':' . $p->due_date->format('Y-m-d');
                $gk = $key . '|' . $p->parthenon_bank_account_id . '|' . $p->paid_at->format('Y-m-d');
                if (!isset($cardGroups[$gk])) {
                    $cardGroups[$gk] = [
                        'ref' => 'card:' . $key,
                        'kind' => 'card',
                        'id' => $p->card_account_id,
                        'account_id' => $p->parthenon_bank_account_id,
                        'date' => $p->paid_at->format('Y-m-d'),
                        'amount' => 0.0,
                        'description' => 'Pagamento da fatura ' . ($p->cardAccount?->display_name ?? 'do cartão') . ' (venc. ' . $p->due_date->format('d/m/Y') . ')',
                        'counterparty' => null,
                        'event' => null,
                        'items' => 0,
                    ];
                }
                $cardGroups[$gk]['amount'] = round($cardGroups[$gk]['amount'] - $amount, 2);
                $cardGroups[$gk]['items']++;
                continue;
            }
            $who = $p->partner?->name ?? $p->collaborator?->name ?? ($p->supplier ? ($p->supplier->fantasy_name ?: $p->supplier->corporate_name) : null);
            $out[] = [
                'ref' => 'payable:' . $p->id,
                'kind' => 'payable',
                'id' => $p->id,
                'account_id' => $p->parthenon_bank_account_id,
                'date' => $p->paid_at->format('Y-m-d'),
                'amount' => -$amount,
                'description' => $p->description,
                'counterparty' => $who,
                'event' => $p->event ? ['id' => $p->event->id, 'name' => $p->event->name] : null,
            ];
        }
        foreach ($cardGroups as $g) {
            $g['description'] .= ' — ' . $g['items'] . ' compra(s)';
            $out[] = $g;
        }

        usort($out, fn ($a, $b) => [$a['date'], $a['ref']] <=> [$b['date'], $b['ref']]);
        return $out;
    }

    private function openingOf(ParthenonBankAccount $a): float
    {
        return (float) ($a->opening_balance ?? 0);
    }

    /** Movimento conta no saldo se for na data do saldo inicial ou depois. */
    private function counts(ParthenonBankAccount $a, array $m): bool
    {
        return !$a->opening_balance_date || $m['date'] >= $a->opening_balance_date->format('Y-m-d');
    }

    private function balanceAt(ParthenonBankAccount $a, array $movements, string $date): float
    {
        $sum = $this->openingOf($a);
        foreach ($movements as $m) {
            if ($m['account_id'] == $a->id && $m['date'] <= $date && $this->counts($a, $m)) {
                $sum += $m['amount'];
            }
        }
        return round($sum, 2);
    }

    private function period(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->format('Y-m-d') : now()->endOfMonth()->format('Y-m-d');
        return [$from, $to];
    }

    /** Cartões do topo (em dia / não bate / sem extrato), resumo do período, saldos e movimentos. */
    public function overview(Request $request)
    {
        [$from, $to] = $this->period($request);
        $accounts = $this->bankAccounts();
        $movements = $this->movements($accounts->pluck('id')->all());
        $accountId = $request->input('account_id');
        $scoped = $accountId ? $accounts->where('id', (int) $accountId)->values() : $accounts;
        $today = now()->format('Y-m-d');

        $lastImports = BankStatementImport::query()
            ->whereNotNull('closing_balance')->whereNotNull('closing_balance_date')
            ->orderByDesc('closing_balance_date')->orderByDesc('id')
            ->get()->groupBy('parthenon_bank_account_id');

        $matchedRefs = BankStatementLine::query()->whereNotNull('matched_ref')->pluck('matched_ref')->flip();

        $cards = $accounts->filter(fn ($a) => $a->active !== false)->map(function ($a) use ($movements, $lastImports, $today) {
            $last = optional($lastImports->get($a->id))->first();
            $balance = $this->balanceAt($a, $movements, $today);
            if (!$last) {
                return ['account' => $this->accountRow($a), 'balance' => $balance, 'status' => 'sem', 'date' => null, 'diff' => 0];
            }
            $date = $last->closing_balance_date->format('Y-m-d');
            $diff = round((float) $last->closing_balance - $this->balanceAt($a, $movements, $date), 2);
            return [
                'account' => $this->accountRow($a),
                'balance' => $balance,
                'status' => abs($diff) < 0.005 ? 'ok' : 'diff',
                'date' => $date,
                'bank_balance' => (float) $last->closing_balance,
                'diff' => $diff,
            ];
        })->values();

        $opening = 0.0;
        $inflow = 0.0;
        $outflow = 0.0;
        $rows = [];
        foreach ($scoped as $a) {
            $opening += $this->openingOf($a);
        }
        $scopeIds = $scoped->pluck('id')->all();
        $byId = $scoped->keyBy('id');
        foreach ($movements as $m) {
            if (!in_array($m['account_id'], $scopeIds) || !$this->counts($byId[$m['account_id']], $m)) {
                continue;
            }
            if ($m['date'] < $from) {
                $opening += $m['amount'];
            } elseif ($m['date'] <= $to) {
                $rows[] = $m;
                if ($m['amount'] >= 0) {
                    $inflow += $m['amount'];
                } else {
                    $outflow += -$m['amount'];
                }
            }
        }
        $running = $opening;
        $names = $accounts->mapWithKeys(fn ($a) => [$a->id => $a->display_name]);
        foreach ($rows as &$r) {
            $running += $r['amount'];
            $r['balance'] = round($running, 2);
            $r['account_name'] = $names[$r['account_id']] ?? '';
            $r['reconciled'] = isset($matchedRefs[$r['ref']]);
        }
        unset($r);

        $balances = $scoped->map(fn ($a) => [
            'account' => $this->accountRow($a),
            'opening_balance' => $this->openingOf($a),
            'balance' => $this->balanceAt($a, $movements, $to),
        ])->values();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'accounts' => $accounts->map(fn ($a) => $this->accountRow($a))->values(),
            'cards' => $cards,
            'summary' => [
                'opening' => round($opening, 2),
                'inflow' => round($inflow, 2),
                'outflow' => round($outflow, 2),
                'final' => round($opening + $inflow - $outflow, 2),
            ],
            'balances' => $balances,
            'movements' => $rows,
        ]);
    }

    /** Conferência com o extrato de uma conta: extratos importados, linhas com sugestão e o que só está no sistema. */
    public function statement(Request $request)
    {
        $request->validate(['account_id' => 'required|exists:parthenon_bank_accounts,id']);
        [$from, $to] = $this->period($request);
        $account = ParthenonBankAccount::findOrFail($request->input('account_id'));

        $imports = BankStatementImport::query()->where('parthenon_bank_account_id', $account->id)
            ->orderByDesc('period_end')->orderByDesc('id')->get();

        $lines = BankStatementLine::query()->where('parthenon_bank_account_id', $account->id)
            ->whereBetween('txn_date', [$from, $to])
            ->orderBy('txn_date')->orderBy('id')->get();

        $movements = array_values(array_filter($this->movements([$account->id]), fn ($m) => $m['date'] >= Carbon::parse($from)->subDays(7)->format('Y-m-d') && $m['date'] <= Carbon::parse($to)->addDays(7)->format('Y-m-d')));
        $byRef = [];
        foreach ($movements as $m) {
            $byRef[$m['ref']] = $m;
        }
        $usedRefs = BankStatementLine::query()->where('parthenon_bank_account_id', $account->id)
            ->whereNotNull('matched_ref')->pluck('matched_ref')->flip()->all();

        $suggestions = $this->suggest($lines->where('status', 'pendente')->values(), $movements, $usedRefs);

        $lineRows = $lines->map(function ($l) use ($byRef, $suggestions) {
            $matched = $l->matched_ref ? ($byRef[$l->matched_ref] ?? null) : null;
            return [
                'id' => $l->id,
                'txn_date' => $l->txn_date->format('Y-m-d'),
                'amount' => (float) $l->amount,
                'description' => $l->description,
                'status' => $l->status,
                'matched_ref' => $l->matched_ref,
                'matched' => $matched ? $this->movementLabel($matched) : ($l->matched_ref ? ['ref' => $l->matched_ref, 'description' => 'Lançamento conciliado'] : null),
                'suggestion' => isset($suggestions[$l->id]) ? $this->movementLabel($suggestions[$l->id]) : null,
            ];
        })->values();

        // No sistema e não no extrato (dentro do período e só se houver extrato que cubra a data)
        $covered = fn ($date) => $imports->contains(fn ($i) => $date >= $i->period_start->format('Y-m-d') && $date <= $i->period_end->format('Y-m-d'));
        $suggestedRefs = [];
        foreach ($suggestions as $m) {
            $suggestedRefs[$m['ref']] = true;
        }
        $systemOnly = [];
        $open = [];
        foreach ($movements as $m) {
            if (isset($usedRefs[$m['ref']]) || $m['date'] < $from || $m['date'] > $to) {
                continue;
            }
            $open[] = $this->movementLabel($m);
            if ($covered($m['date']) && !isset($suggestedRefs[$m['ref']])) {
                $systemOnly[] = $this->movementLabel($m);
            }
        }

        return response()->json([
            'account' => $this->accountRow($account),
            'imports' => $imports->map(fn ($i) => [
                'id' => $i->id,
                'file_name' => $i->file_name,
                'file_format' => $i->file_format,
                'period_start' => $i->period_start->format('Y-m-d'),
                'period_end' => $i->period_end->format('Y-m-d'),
                'closing_balance' => $i->closing_balance !== null ? (float) $i->closing_balance : null,
                'closing_balance_date' => $i->closing_balance_date?->format('Y-m-d'),
                'lines_total' => $i->lines_total,
                'lines_new' => $i->lines_new,
                'created_at' => $i->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'lines' => $lineRows,
            'counts' => [
                'pendente' => $lines->where('status', 'pendente')->count(),
                'conciliado' => $lines->where('status', 'conciliado')->count(),
                'ignorado' => $lines->where('status', 'ignorado')->count(),
                'sugeridas' => count($suggestions),
            ],
            'system_only' => $systemOnly,
            'open_movements' => $open,
        ]);
    }

    /** Importa as linhas do extrato (o arquivo é lido na tela). Linha repetida (mesma chave) não entra de novo. */
    public function import(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|exists:parthenon_bank_accounts,id',
            'file_name' => 'nullable|string|max:255',
            'file_format' => 'required|in:ofx,csv,xlsx',
            'closing_balance' => 'nullable|numeric',
            'closing_balance_date' => 'nullable|date',
            'lines' => 'required|array|min:1',
            'lines.*.txn_date' => 'required|date',
            'lines.*.amount' => 'required|numeric|not_in:0',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.doc_key' => 'required|string|max:255',
        ], ['lines.required' => 'Não encontrei lançamentos neste arquivo.']);

        $dates = array_map(fn ($l) => Carbon::parse($l['txn_date'])->format('Y-m-d'), $data['lines']);
        sort($dates);

        $import = DB::transaction(function () use ($data, $dates, $request) {
            $import = BankStatementImport::create([
                'parthenon_bank_account_id' => $data['account_id'],
                'file_name' => $data['file_name'] ?? null,
                'file_format' => $data['file_format'],
                'period_start' => $dates[0],
                'period_end' => end($dates),
                'closing_balance' => $data['closing_balance'] ?? null,
                'closing_balance_date' => isset($data['closing_balance']) ? ($data['closing_balance_date'] ?? end($dates)) : null,
                'lines_total' => count($data['lines']),
                'created_by' => $request->user()?->id,
            ]);
            $new = 0;
            foreach ($data['lines'] as $l) {
                $exists = BankStatementLine::query()
                    ->where('parthenon_bank_account_id', $data['account_id'])
                    ->where('doc_key', $l['doc_key'])->exists();
                if ($exists) {
                    continue;
                }
                BankStatementLine::create([
                    'import_id' => $import->id,
                    'parthenon_bank_account_id' => $data['account_id'],
                    'txn_date' => Carbon::parse($l['txn_date'])->format('Y-m-d'),
                    'amount' => round((float) $l['amount'], 2),
                    'description' => isset($l['description']) ? mb_substr($l['description'], 0, 255) : null,
                    'doc_key' => $l['doc_key'],
                    'status' => 'pendente',
                ]);
                $new++;
            }
            $import->update(['lines_new' => $new]);
            return $import;
        });

        return response()->json(['id' => $import->id, 'lines_total' => $import->lines_total, 'lines_new' => $import->lines_new, 'period_start' => $dates[0], 'period_end' => end($dates)], 201);
    }

    /** Excluir um extrato importado (as linhas dele saem junto). */
    public function destroyImport(BankStatementImport $import)
    {
        $import->delete();
        return response()->json([], 204);
    }

    /** Conciliar a linha com um movimento do sistema (ref = receivable:ID, payable:ID ou card:ID:data). */
    public function match(Request $request, BankStatementLine $line)
    {
        $data = $request->validate(['ref' => 'required|string|max:60']);
        $taken = BankStatementLine::query()->where('parthenon_bank_account_id', $line->parthenon_bank_account_id)
            ->where('matched_ref', $data['ref'])->where('id', '!=', $line->id)->exists();
        if ($taken) {
            return response()->json(['message' => 'Este lançamento já está conciliado com outra linha do extrato.'], 422);
        }
        $line->update(['status' => 'conciliado', 'matched_ref' => $data['ref']]);
        return response()->json(['ok' => true]);
    }

    /** Aceitar todas as sugestões do período de uma vez. */
    public function matchSuggested(Request $request)
    {
        $request->validate(['account_id' => 'required|exists:parthenon_bank_accounts,id']);
        [$from, $to] = $this->period($request);
        $accountId = (int) $request->input('account_id');
        $lines = BankStatementLine::query()->where('parthenon_bank_account_id', $accountId)
            ->where('status', 'pendente')->whereBetween('txn_date', [$from, $to])->orderBy('txn_date')->get();
        $movements = $this->movements([$accountId]);
        $used = BankStatementLine::query()->where('parthenon_bank_account_id', $accountId)
            ->whereNotNull('matched_ref')->pluck('matched_ref')->flip()->all();
        $suggestions = $this->suggest($lines, $movements, $used);
        foreach ($suggestions as $lineId => $m) {
            BankStatementLine::where('id', $lineId)->update(['status' => 'conciliado', 'matched_ref' => $m['ref']]);
        }
        return response()->json(['matched' => count($suggestions)]);
    }

    public function ignore(BankStatementLine $line)
    {
        $line->update(['status' => 'ignorado', 'matched_ref' => null]);
        return response()->json(['ok' => true]);
    }

    public function reopen(BankStatementLine $line)
    {
        $line->update(['status' => 'pendente', 'matched_ref' => null]);
        return response()->json(['ok' => true]);
    }

    /**
     * Lançar a linha do extrato que não existe no sistema (tarifa, rendimento, pix avulso...):
     * saída vira conta a pagar já paga; entrada vira conta a receber já recebida. A linha fica conciliada.
     */
    public function launch(Request $request, BankStatementLine $line)
    {
        $data = $request->validate([
            'description' => 'required|string|max:255',
            'financial_operation_id' => 'nullable|exists:financial_operations,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'event_id' => 'nullable|exists:events,id',
            'category' => 'nullable|in:terceirizado,mao_de_obra,hotel,lanche,bv,nota,compra,outro',
            'notes' => 'nullable|string',
        ], ['description.required' => 'Informe a descrição.']);

        $amount = abs((float) $line->amount);
        $date = $line->txn_date->format('Y-m-d');

        $ref = DB::transaction(function () use ($data, $line, $amount, $date, $request) {
            if ((float) $line->amount < 0) {
                $payable = Payable::create([
                    'description' => $data['description'],
                    'category' => $data['category'] ?? 'outro',
                    'amount' => $amount,
                    'due_date' => $date,
                    'paid_at' => $date . ' 12:00:00',
                    'paid_amount' => $amount,
                    'status' => 'paid',
                    'approval_status' => 'approved',
                    'approved_by' => $request->user()?->id,
                    'approved_at' => now(),
                    'financial_operation_id' => $data['financial_operation_id'] ?? null,
                    'cost_center_id' => $data['cost_center_id'] ?? null,
                    'event_id' => $data['event_id'] ?? null,
                    'parthenon_bank_account_id' => $line->parthenon_bank_account_id,
                    'notes' => $data['notes'] ?? 'Lançado pela conciliação bancária',
                ]);
                $ref = 'payable:' . $payable->id;
            } else {
                $event = !empty($data['event_id']) ? Event::find($data['event_id']) : null;
                $receivable = Receivable::create([
                    'description' => $data['description'],
                    'amount' => $amount,
                    'due_date' => $date,
                    'paid_at' => $date . ' 12:00:00',
                    'received_amount' => $amount,
                    'status' => 'paid',
                    'financial_operation_id' => $data['financial_operation_id'] ?? null,
                    'parthenon_bank_account_id' => $line->parthenon_bank_account_id,
                    'settled_by' => $request->user()?->id,
                    'notes' => $data['notes'] ?? 'Lançado pela conciliação bancária',
                    'receivableable_type' => $event ? Event::class : null,
                    'receivableable_id' => $event?->id,
                ]);
                $ref = 'receivable:' . $receivable->id;
            }
            $line->update(['status' => 'conciliado', 'matched_ref' => $ref]);
            return $ref;
        });

        return response()->json(['ok' => true, 'ref' => $ref], 201);
    }

    // ---------------------------------------------------------------

    /**
     * Sugestão para cada linha pendente: movimento com o mesmo valor (mesmo sinal), até 5 dias de diferença,
     * ainda não conciliado. A mais próxima na data ganha; um movimento só serve para uma linha.
     *
     * @return array<int, array> linha => movimento
     */
    private function suggest($lines, array $movements, array $usedRefs): array
    {
        $pairs = [];
        foreach ($lines as $l) {
            $ld = $l->txn_date->format('Y-m-d');
            foreach ($movements as $m) {
                if (isset($usedRefs[$m['ref']])) {
                    continue;
                }
                if (abs(round((float) $l->amount, 2) - round($m['amount'], 2)) >= 0.005) {
                    continue;
                }
                $days = abs(Carbon::parse($ld)->diffInDays(Carbon::parse($m['date']), false));
                if ($days > 5) {
                    continue;
                }
                $pairs[] = [$days, $l->id, $m];
            }
        }
        usort($pairs, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        $byLine = [];
        $taken = [];
        foreach ($pairs as [$days, $lineId, $m]) {
            if (isset($byLine[$lineId]) || isset($taken[$m['ref']])) {
                continue;
            }
            $byLine[$lineId] = $m;
            $taken[$m['ref']] = true;
        }
        return $byLine;
    }

    private function movementLabel(array $m): array
    {
        return [
            'ref' => $m['ref'],
            'kind' => $m['kind'],
            'id' => $m['id'],
            'date' => $m['date'],
            'amount' => $m['amount'],
            'description' => $m['description'],
            'counterparty' => $m['counterparty'] ?? null,
            'event' => $m['event'] ?? null,
        ];
    }

    private function accountRow(ParthenonBankAccount $a): array
    {
        return [
            'id' => $a->id,
            'name' => $a->display_name,
            'account_kind' => $a->account_kind ?: 'banco',
            'bank_name' => $a->bank_name,
            'account_number' => $a->account_number,
            'active' => $a->active !== false,
        ];
    }
}
