<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPayment;
use Carbon\Carbon;

/**
 * Dados do PDF "Fechamento do evento" (mesmo conteúdo do Parthenon): resumo
 * financeiro, pagamentos realizados, parcelas em aberto e saldo.
 * A conta é a mesma do resumo da aba Fechamento (front:
 * manage-event-payments-data-index); mudar uma exige mudar a outra.
 */
class EventClosingService
{
    public static function data(Event $event): array
    {
        $event->load(['client', 'eventPlace', 'extraSales', 'breakItems', 'receivables.paymentMethod', 'eventBatches']);

        $guests = (int) $event->event_guests;
        $unit = (float) $event->guest_unit_value - (float) $event->unit_discount;
        $insurance = (float) $event->unit_insurance;
        $courtesies = (int) $event->parthenon_courtesies + (int) $event->partner_courtesies;
        $batchGuests = (int) $event->eventBatches->sum('guests');
        $batchesValue = (float) $event->eventBatches->sum(fn ($b) => (float) $b->unit_value * (int) $b->guests);
        $contracted = $guests + $courtesies + $batchGuests;
        $present = (int) $event->present_guests;
        $excessGuests = max(0, $present - $contracted);
        // Excedente vazio: cobra pelo valor por convidado.
        $excessUnit = (float) $event->excess_unit_value ?: (float) $event->guest_unit_value;
        $excess = $excessGuests * $excessUnit;
        $extras = (float) $event->extraSales->sum(fn ($s) => (float) $s->quantity * (float) $s->unit_value);
        $breaks = (float) $event->breakItems->sum(fn ($i) => (float) $i->pivot->quantity * (float) $i->pivot->value);

        $rows = [
            ['Serviço de bar (' . $guests . ' convidados × ' . currencyMask($unit) . ')', $guests * $unit, true],
            ['Seguro (' . $guests . ' × ' . currencyMask($insurance) . ')', $guests * $insurance, false],
            ['Lotes adicionais (' . $batchGuests . ' convidados)', $batchesValue, false],
            ['Locação', (float) $event->lease, false],
            ['Aditivos', (float) $event->addendum_value, false],
            ['Convidados excedentes (' . $excessGuests . ' × ' . currencyMask($excessUnit) . ')', $excess, false],
            ['Vendas extras', $extras, false],
            ['Quebras e avarias', $breaks, false],
            ['Desconto', -(float) $event->discount, false],
        ];
        $total = round(array_sum(array_column($rows, 1)), 2);
        // Valores zerados ficam de fora, menos a primeira linha.
        $summary = array_values(array_filter($rows, fn ($row) => $row[2] || abs($row[1]) >= 0.005));

        $paidReceivables = $event->receivables->filter(fn ($r) => $r->status === 'paid' || !is_null($r->paid_at));
        $payments = $paidReceivables->map(fn ($r) => [
            'date' => $r->paid_at ?? $r->due_date,
            'amount' => (float) $r->amount,
            'method' => $r->paymentMethod?->name,
            'description' => $r->description,
        ])->concat(EventPayment::with('paymentMethod')->where('event_id', $event->id)->get()->map(fn ($p) => [
            'date' => $p->date,
            'amount' => (float) $p->value,
            'method' => $p->paymentMethod?->name,
            'description' => $p->comment,
        ]))->sortBy(fn ($p) => (string) $p['date'])->values()->all();

        $pending = $event->receivables
            ->filter(fn ($r) => $r->status !== 'paid' && is_null($r->paid_at))
            ->sortBy('due_date')
            ->map(fn ($r) => [
                'date' => $r->due_date,
                'amount' => (float) $r->amount,
                'method' => $r->paymentMethod?->name,
                'description' => $r->description,
                'late' => $r->due_date && Carbon::parse($r->due_date)->endOfDay()->isPast(),
            ])->values()->all();

        $paid = round(array_sum(array_column($payments, 'amount')), 2);
        $pendingSum = round(array_sum(array_column($pending, 'amount')), 2);
        $remaining = round($total - $paid, 2);
        $client = $event->client;

        return [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date ? Carbon::parse($event->date)->format('d/m/Y') : 'A definir',
                'place' => $event->eventPlace?->name ?: '—',
                'guests' => $guests + $batchGuests,
                'courtesies' => $courtesies,
                'contracted' => $contracted,
                'present' => $present ?: null,
                'excess_guests' => $excessGuests,
            ],
            'client' => [
                'name' => $client?->name ?: '—',
                'document' => $client?->cnpj ?: $client?->cpf,
                'phone' => $client?->phone_1,
                'email' => $client?->email,
            ],
            'summary' => $summary,
            'payments' => $payments,
            'pending' => $pending,
            'total' => $total,
            'paid' => $paid,
            'pending_sum' => $pendingSum,
            'remaining' => max(0, $remaining),
            'credit' => $remaining < -0.009 ? abs($remaining) : 0,
            // Saldo que ainda não tem parcela gerada.
            'unscheduled' => $pending && $remaining - $pendingSum > 0.009 ? round($remaining - $pendingSum, 2) : 0,
            'issued_at' => Carbon::now()->format('d/m/Y'),
        ];
    }

    public static function fileName(Event $event): string
    {
        return 'fechamento-' . $event->id . '-' . time();
    }
}
