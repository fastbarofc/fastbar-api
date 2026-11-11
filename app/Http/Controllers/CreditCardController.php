<?php

namespace App\Http\Controllers;

use App\Http\Resources\PayableResource;
use App\Models\CardInvoiceLine;
use App\Models\ParthenonBankAccount;
use App\Models\Payable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Financeiro > Cartão de crédito (formato do sistema da MAV).
 *
 * Compras no cartão são contas a pagar com card_account_id; o vencimento é o da fatura
 * (compra até o dia do fechamento entra na fatura do mês; depois, na do mês seguinte).
 * A fatura importada (OFX/CSV) é conferida linha a linha: vincular a uma conta a pagar, lançar ou ignorar.
 * "Pagar fatura" dá baixa em todas as compras em aberto da fatura, pela conta do banco escolhida.
 */
class CreditCardController extends Controller
{
    public function index()
    {
        $cards = ParthenonBankAccount::query()->where('account_kind', 'cartao_credito')
            ->orderBy('name')->get()
            ->map(fn ($c) => $this->cardRow($c))->values();
        return response()->json(['data' => $cards]);
    }

    /** Vencimento da fatura em que entra uma compra feita em `date`. */
    public function statementDue(Request $request, ParthenonBankAccount $card)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        return response()->json(['due_date' => self::dueFor($card, $date)]);
    }

    public static function dueFor(ParthenonBankAccount $card, string $purchaseDate): string
    {
        $d = Carbon::parse($purchaseDate);
        $closing = (int) ($card->closing_day ?: 1);
        $due = (int) ($card->due_day ?: 10);
        $month = Carbon::create($d->year, $d->month, 1);
        if ($d->day > $closing) {
            $month->addMonth();
        }
        if ($due <= $closing) {
            $month->addMonth();
        }
        $day = min($due, $month->daysInMonth);
        return $month->copy()->day($day)->format('Y-m-d');
    }

    /** Uma fatura: lista de vencimentos, compras lançadas, totais e linhas importadas. */
    public function invoice(Request $request, ParthenonBankAccount $card)
    {
        $entries = Payable::query()->where('card_account_id', $card->id)
            ->with(['event:id,name,date', 'supplier', 'partner:id,name', 'collaborator:id,name', 'financialOperation', 'costCenter'])
            ->orderBy('due_date')->orderBy('id')->get();

        $importedDues = CardInvoiceLine::query()->where('card_account_id', $card->id)
            ->distinct()->pluck('due_date')->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'));

        $statements = $entries->map(fn ($e) => $e->due_date->format('Y-m-d'))
            ->merge($importedDues)->unique()->sort()->values();

        $dueDate = $request->input('due_date');
        if (!$dueDate || !$statements->contains($dueDate)) {
            $open = $entries->first(fn ($e) => !$e->paid_at);
            $dueDate = $open ? $open->due_date->format('Y-m-d') : ($statements->last() ?? null);
        }

        $rows = $entries->filter(fn ($e) => $dueDate && $e->due_date->format('Y-m-d') === $dueDate)->values();
        $sum = fn ($list) => round($list->sum(fn ($e) => (float) $e->amount), 2);
        $openRows = $rows->filter(fn ($e) => !$e->paid_at);
        $paidRows = $rows->filter(fn ($e) => (bool) $e->paid_at);

        $lines = $dueDate ? CardInvoiceLine::query()->where('card_account_id', $card->id)
            ->whereDate('due_date', $dueDate)->orderBy('txn_date')->orderBy('id')->get() : collect();

        $linkedIds = $lines->pluck('payable_id')->filter()->flip();
        $suggestions = $this->suggest($lines->where('status', 'pendente')->values(), $rows->filter(fn ($e) => !isset($linkedIds[$e->id]))->values());

        return response()->json([
            'card' => $this->cardRow($card),
            'statements' => $statements,
            'due_date' => $dueDate,
            'entries' => PayableResource::collection($rows),
            'totals' => [
                'total' => $sum($rows),
                'open' => $sum($openRows),
                'paid' => $sum($paidRows),
                'open_count' => $openRows->count(),
            ],
            'lines' => $lines->map(fn ($l) => [
                'id' => $l->id,
                'txn_date' => $l->txn_date->format('Y-m-d'),
                'description' => $l->description,
                'amount' => (float) $l->amount,
                'installment_number' => $l->installment_number,
                'installment_total' => $l->installment_total,
                'status' => $l->status,
                'payable_id' => $l->payable_id,
                'suggestion' => isset($suggestions[$l->id]) ? [
                    'id' => $suggestions[$l->id]->id,
                    'description' => $suggestions[$l->id]->description,
                    'amount' => (float) $suggestions[$l->id]->amount,
                ] : null,
            ])->values(),
            'line_totals' => [
                'total' => round($lines->where('status', '!=', 'ignorado')->sum(fn ($l) => (float) $l->amount), 2),
                'pendente' => $lines->where('status', 'pendente')->count(),
            ],
        ]);
    }

    /** Pagar a fatura: baixa em todas as compras em aberto do vencimento. */
    public function pay(Request $request, ParthenonBankAccount $card)
    {
        $data = $request->validate([
            'due_date' => 'required|date',
            'paid_at' => 'required|date',
            'parthenon_bank_account_id' => 'required|exists:parthenon_bank_accounts,id',
        ], [
            'parthenon_bank_account_id.required' => 'Escolha a conta usada para pagar a fatura.',
            'paid_at.required' => 'Informe a data do pagamento.',
        ]);
        $items = Payable::query()->where('card_account_id', $card->id)
            ->whereDate('due_date', $data['due_date'])
            ->whereNull('paid_at')
            ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', 'approved'))
            ->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Não há compras em aberto nesta fatura.'], 422);
        }
        DB::transaction(function () use ($items, $data) {
            foreach ($items as $p) {
                $p->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::parse($data['paid_at'])->format('Y-m-d') . ' 12:00:00',
                    'paid_amount' => $p->amount,
                    'parthenon_bank_account_id' => $data['parthenon_bank_account_id'],
                ]);
            }
        });
        return response()->json(['paid' => $items->count(), 'total' => round($items->sum(fn ($p) => (float) $p->amount), 2)]);
    }

    /** Desfazer o pagamento da fatura (volta as compras para em aberto). */
    public function unpay(Request $request, ParthenonBankAccount $card)
    {
        $data = $request->validate(['due_date' => 'required|date']);
        $count = Payable::query()->where('card_account_id', $card->id)
            ->whereDate('due_date', $data['due_date'])->whereNotNull('paid_at')
            ->update(['status' => 'pending', 'paid_at' => null, 'paid_amount' => null, 'parthenon_bank_account_id' => null]);
        return response()->json(['reopened' => $count]);
    }

    /** Importar as linhas da fatura (o arquivo é lido na tela). Pagamento da fatura anterior entra já como ignorado. */
    public function import(Request $request, ParthenonBankAccount $card)
    {
        $data = $request->validate([
            'due_date' => 'required|date',
            'file_name' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.txn_date' => 'required|date',
            'lines.*.amount' => 'required|numeric|not_in:0',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.doc_key' => 'required|string|max:255',
            'lines.*.installment_number' => 'nullable|integer',
            'lines.*.installment_total' => 'nullable|integer',
        ], ['lines.required' => 'Não encontrei lançamentos neste arquivo.']);

        $due = Carbon::parse($data['due_date'])->format('Y-m-d');
        $new = 0;
        foreach ($data['lines'] as $l) {
            $exists = CardInvoiceLine::query()->where('card_account_id', $card->id)
                ->whereDate('due_date', $due)->where('doc_key', $l['doc_key'])->exists();
            if ($exists) {
                continue;
            }
            $desc = isset($l['description']) ? mb_substr($l['description'], 0, 255) : null;
            $isPayment = (bool) preg_match('/^\s*pagamento\b/iu', (string) $desc) || (float) $l['amount'] < 0 && preg_match('/pagto|pagamento/iu', (string) $desc);
            CardInvoiceLine::create([
                'card_account_id' => $card->id,
                'due_date' => $due,
                'txn_date' => Carbon::parse($l['txn_date'])->format('Y-m-d'),
                'description' => $desc,
                'amount' => round((float) $l['amount'], 2),
                'installment_number' => $l['installment_number'] ?? null,
                'installment_total' => $l['installment_total'] ?? null,
                'doc_key' => $l['doc_key'],
                'file_name' => $data['file_name'] ?? null,
                'status' => $isPayment ? 'ignorado' : 'pendente',
                'created_by' => $request->user()?->id,
            ]);
            $new++;
        }
        return response()->json(['lines_total' => count($data['lines']), 'lines_new' => $new, 'due_date' => $due], 201);
    }

    /** Vincular a linha a uma compra já lançada. */
    public function link(Request $request, CardInvoiceLine $line)
    {
        $data = $request->validate(['payable_id' => 'required|exists:payables,id']);
        $line->update(['status' => 'vinculado', 'payable_id' => $data['payable_id']]);
        return response()->json(['ok' => true]);
    }

    /** Lançar a linha como compra nova no cartão (conta a pagar com o vencimento da fatura). */
    public function launch(Request $request, CardInvoiceLine $line)
    {
        $data = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'nullable|in:terceirizado,mao_de_obra,hotel,lanche,bv,nota,compra,outro',
            'financial_operation_id' => 'nullable|exists:financial_operations,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'event_id' => 'nullable|exists:events,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string',
        ], ['description.required' => 'Informe a descrição.']);

        $payable = DB::transaction(function () use ($data, $line, $request) {
            $payable = Payable::create([
                'description' => $data['description'],
                'category' => $data['category'] ?? 'compra',
                'amount' => abs((float) $line->amount),
                'due_date' => $line->due_date->format('Y-m-d'),
                'status' => 'pending',
                'approval_status' => 'approved',
                'approved_by' => $request->user()?->id,
                'approved_at' => now(),
                'financial_operation_id' => $data['financial_operation_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'card_account_id' => $line->card_account_id,
                'notes' => $data['notes'] ?? ('Compra no cartão em ' . $line->txn_date->format('d/m/Y')),
            ]);
            $line->update(['status' => 'lancado', 'payable_id' => $payable->id]);
            return $payable;
        });

        return response()->json(['ok' => true, 'payable_id' => $payable->id], 201);
    }

    /** Aceitar todas as sugestões (mesmo valor) da fatura. */
    public function linkSuggested(Request $request, ParthenonBankAccount $card)
    {
        $data = $request->validate(['due_date' => 'required|date']);
        $due = Carbon::parse($data['due_date'])->format('Y-m-d');
        $lines = CardInvoiceLine::query()->where('card_account_id', $card->id)->whereDate('due_date', $due)->get();
        $linked = $lines->pluck('payable_id')->filter()->flip();
        $entries = Payable::query()->where('card_account_id', $card->id)->whereDate('due_date', $due)->get()
            ->filter(fn ($e) => !isset($linked[$e->id]))->values();
        $suggestions = $this->suggest($lines->where('status', 'pendente')->values(), $entries);
        foreach ($suggestions as $lineId => $payable) {
            CardInvoiceLine::where('id', $lineId)->update(['status' => 'vinculado', 'payable_id' => $payable->id]);
        }
        return response()->json(['linked' => count($suggestions)]);
    }

    public function ignore(CardInvoiceLine $line)
    {
        $line->update(['status' => 'ignorado', 'payable_id' => null]);
        return response()->json(['ok' => true]);
    }

    /** Voltar a linha para pendente. Se ela tinha sido lançada aqui e a compra não foi paga, a compra é apagada. */
    public function reopen(CardInvoiceLine $line)
    {
        DB::transaction(function () use ($line) {
            if ($line->status === 'lancado' && $line->payable && !$line->payable->paid_at) {
                $line->payable->delete();
            }
            $line->update(['status' => 'pendente', 'payable_id' => null]);
        });
        return response()->json(['ok' => true]);
    }

    /** Excluir as linhas importadas de uma fatura (as compras lançadas continuam). */
    public function clearImport(Request $request, ParthenonBankAccount $card)
    {
        $data = $request->validate(['due_date' => 'required|date']);
        $count = CardInvoiceLine::query()->where('card_account_id', $card->id)
            ->whereDate('due_date', $data['due_date'])->delete();
        return response()->json(['deleted' => $count]);
    }

    // ---------------------------------------------------------------

    /** Linha pendente x compra lançada com o mesmo valor (a mais próxima na descrição/ordem). */
    private function suggest($lines, $entries): array
    {
        $out = [];
        $taken = [];
        foreach ($lines as $l) {
            foreach ($entries as $e) {
                if (isset($taken[$e->id])) {
                    continue;
                }
                if (abs(abs((float) $l->amount) - (float) $e->amount) < 0.005) {
                    $out[$l->id] = $e;
                    $taken[$e->id] = true;
                    break;
                }
            }
        }
        return $out;
    }

    private function cardRow(ParthenonBankAccount $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->display_name,
            'bank_name' => $c->bank_name,
            'card_brand' => $c->card_brand,
            'card_last_digits' => $c->card_last_digits,
            'closing_day' => $c->closing_day,
            'due_day' => $c->due_day,
            'active' => $c->active !== false,
        ];
    }
}
