{{-- Fechamento do evento: mesmo conteúdo do Parthenon, com layout moderno nas cores da Fast Bar (preto, magenta, ciano e amarelo). --}}
@php
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $day = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Fechamento do evento {{ $closing['event']['id'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #17171a; font-size: 12px; line-height: 1.45; margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; padding: 0; text-align: left; }
        p { margin: 0; }
        .muted { color: #6b6f7b; }
        .right { text-align: right; }
        .nowrap { white-space: nowrap; }

        .stripe td { height: 6px; }
        .head td { padding: 20px 0 16px 0; vertical-align: middle; }
        .head img { height: 58px; }
        .head h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.3px; text-align: right; }
        .head .meta { text-align: right; font-size: 11px; color: #6b6f7b; margin-top: 4px; }
        .head .meta b { color: #17171a; }

        .lbl { display: block; font-size: 9px; text-transform: uppercase; letter-spacing: 1.2px; color: #6b6f7b; font-weight: 700; margin-bottom: 2px; }

        .card { background: #f5f6f8; border-radius: 10px; margin: 4px 0 16px 0; }
        .card td { padding: 7px 18px; }
        .card td.name { font-size: 17px; font-weight: 800; padding-top: 14px; padding-bottom: 4px; }
        .card tr.last td { padding-bottom: 14px; }

        .kpi { margin-bottom: 18px; }
        .kpi td { width: 33.33%; padding: 0 10px 0 0; }
        .kpi td.k3 { padding-right: 0; }
        .tile { border-radius: 10px; padding: 11px 16px 12px 16px; background: #f5f6f8; border-left: 5px solid #27aae1; }
        .tile.paid { border-left-color: #f5b800; }
        .tile.due { background: #17171a; color: #fff; border-left-color: #ec1c6e; }
        .tile.due .lbl { color: #c9ccd4; }
        .tile .val { font-size: 20px; font-weight: 800; line-height: 1.2; white-space: nowrap; }

        .section { font-size: 12px; font-weight: 800; margin: 18px 0 7px 0; }
        .section span { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: #ec1c6e; margin-right: 7px; }
        .section.cyan span { background: #27aae1; }
        .section.yellow span { background: #f5b800; }

        .grid thead { display: table-header-group; }
        .grid thead th { background: #17171a; color: #fff; font-size: 9.5px; text-transform: uppercase; letter-spacing: 1px; padding: 8px 12px; font-weight: 700; }
        .grid thead th:first-child { border-radius: 8px 0 0 0; }
        .grid thead th:last-child { border-radius: 0 8px 0 0; }
        .grid tbody td { padding: 7px 12px; border-bottom: 1px solid #e6e8ee; }
        .grid tbody tr.zebra td { background: #fafbfc; }
        .grid tbody tr.total td { font-weight: 800; background: #f5f6f8; border-bottom: none; border-top: 2px solid #17171a; }
        .grid .amount { text-align: right; white-space: nowrap; }
        .grid tr, .tot tr, .kpi tr { page-break-inside: avoid; }

        .chip { display: inline-block; padding: 1px 9px; border-radius: 10px; font-size: 10px; font-weight: 700; }
        .chip-ok { background: #dff3fb; color: #0f6f99; }
        .chip-due { background: #fff3cc; color: #8a6400; }
        .chip-late { background: #fde0ec; color: #b01053; }

        .tot { width: 300px; margin: 18px 0 0 auto; }
        .tot td { padding: 6px 14px; font-size: 12.5px; }
        .tot td.amount { text-align: right; white-space: nowrap; font-weight: 700; }
        .tot tr.big td { background: #ec1c6e; color: #fff; font-weight: 800; font-size: 15px; padding: 10px 14px; }
        .tot tr.big td:first-child { border-radius: 8px 0 0 8px; }
        .tot tr.big td:last-child { border-radius: 0 8px 8px 0; }

        .thanks { margin-top: 26px; text-align: center; font-style: italic; color: #6b6f7b; }
        .foot { border-top: 1px solid #e6e8ee; margin-top: 18px; }
        .foot td { padding-top: 9px; font-size: 10px; color: #6b6f7b; }
    </style>
</head>

<body>
    <table class="stripe"><tbody><tr>
        <td style="background: #ec1c6e; width: 40%;"></td>
        <td style="background: #27aae1; width: 25%;"></td>
        <td style="background: #f5b800; width: 15%;"></td>
        <td style="background: #17171a; width: 20%;"></td>
    </tr></tbody></table>

    <table class="head"><tbody><tr>
        <td style="width: 40%;"><img src="{{ $brand['logo'] }}" alt="Fast Bar"></td>
        <td>
            <h1>Fechamento do evento</h1>
            <div class="meta">Evento <b>nº {{ $closing['event']['id'] }}</b> &nbsp;·&nbsp; Emitido em <b>{{ $closing['issued_at'] }}</b></div>
        </td>
    </tr></tbody></table>

    <table class="card"><tbody>
        <tr><td class="name" colspan="3">{{ $closing['event']['name'] }}</td></tr>
        <tr>
            <td style="width: 40%;"><span class="lbl">Cliente</span>{{ $closing['client']['name'] }}@if ($closing['client']['document']) · {{ $closing['client']['document'] }}@endif</td>
            <td style="width: 30%;"><span class="lbl">Data</span>{{ $closing['event']['date'] }}</td>
            <td style="width: 30%;"><span class="lbl">Local</span>{{ $closing['event']['place'] }}</td>
        </tr>
        <tr class="last">
            <td><span class="lbl">Contato</span>{{ $closing['client']['phone'] ?: '—' }}@if ($closing['client']['email']) · {{ $closing['client']['email'] }}@endif</td>
            <td><span class="lbl">Convidados</span>{{ $closing['event']['guests'] }} contratados + {{ $closing['event']['courtesies'] }} cortesias = {{ $closing['event']['contracted'] }}</td>
            <td><span class="lbl">Presentes</span>{{ $closing['event']['present'] ?? '—' }}@if ($closing['event']['excess_guests']) ({{ $closing['event']['excess_guests'] }} excedentes)@endif</td>
        </tr>
    </tbody></table>

    <table class="kpi"><tbody><tr>
        <td><div class="tile"><span class="lbl">Total do evento</span><div class="val">{{ $money($closing['total']) }}</div></div></td>
        <td><div class="tile paid"><span class="lbl">Total pago</span><div class="val">{{ $money($closing['paid']) }}</div></div></td>
        <td class="k3"><div class="tile due"><span class="lbl">Total a receber</span><div class="val">{{ $money($closing['remaining']) }}</div></div></td>
    </tr></tbody></table>

    <div class="section"><span></span>Resumo financeiro</div>
    <table class="grid">
        <thead><tr><th>Item</th><th class="amount">Valor</th></tr></thead>
        <tbody>
            @foreach ($closing['summary'] as $i => [$label, $value])
                <tr @class(['zebra' => $i % 2 === 1])>
                    <td>{{ $label }}</td>
                    <td class="amount">{{ $value < 0 ? '- ' . $money(abs($value)) : $money($value) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td>Total</td><td class="amount">{{ $money($closing['total']) }}</td></tr>
        </tbody>
    </table>

    <div class="section cyan"><span></span>Pagamentos realizados</div>
    @if ($closing['payments'])
        <table class="grid">
            <thead><tr><th style="width: 85px;">Data</th><th style="width: 150px;">Forma</th><th>Descrição</th><th style="width: 90px;">Situação</th><th class="amount">Valor</th></tr></thead>
            <tbody>
                @foreach ($closing['payments'] as $i => $payment)
                    <tr @class(['zebra' => $i % 2 === 1])>
                        <td class="nowrap">{{ $day($payment['date']) }}</td>
                        <td>{{ $payment['method'] ?: '—' }}</td>
                        <td class="muted">{{ $payment['description'] }}</td>
                        <td><span class="chip chip-ok">Pago</span></td>
                        <td class="amount">{{ $money($payment['amount']) }}</td>
                    </tr>
                @endforeach
                <tr class="total"><td colspan="4">Total pago</td><td class="amount">{{ $money($closing['paid']) }}</td></tr>
            </tbody>
        </table>
    @else
        <p class="muted">Nenhum pagamento registrado até o momento.</p>
    @endif

    @if ($closing['pending'])
        <div class="section yellow"><span></span>Parcelas em aberto</div>
        <table class="grid">
            <thead><tr><th style="width: 85px;">Vencimento</th><th style="width: 150px;">Forma</th><th>Descrição</th><th style="width: 90px;">Situação</th><th class="amount">Valor</th></tr></thead>
            <tbody>
                @foreach ($closing['pending'] as $i => $payment)
                    <tr @class(['zebra' => $i % 2 === 1])>
                        <td class="nowrap">{{ $day($payment['date']) }}</td>
                        <td>{{ $payment['method'] ?: '—' }}</td>
                        <td class="muted">{{ $payment['description'] }}</td>
                        <td><span class="chip {{ $payment['late'] ? 'chip-late' : 'chip-due' }}">{{ $payment['late'] ? 'Vencida' : 'A vencer' }}</span></td>
                        <td class="amount">{{ $money($payment['amount']) }}</td>
                    </tr>
                @endforeach
                <tr class="total"><td colspan="4">Total em aberto</td><td class="amount">{{ $money($closing['pending_sum']) }}</td></tr>
            </tbody>
        </table>
        @if ($closing['unscheduled'])
            <p class="muted" style="margin-top: 6px; font-size: 11px;">Valor sem parcela gerada: <b>{{ $money($closing['unscheduled']) }}</b> (saldo restante maior que a soma das parcelas em aberto).</p>
        @endif
    @endif

    <table class="tot"><tbody>
        <tr><td>Total do evento</td><td class="amount">{{ $money($closing['total']) }}</td></tr>
        <tr><td>Total pago</td><td class="amount">{{ $money($closing['paid']) }}</td></tr>
        <tr class="big"><td>Total a receber</td><td class="amount">{{ $money($closing['remaining']) }}</td></tr>
        @if ($closing['credit'])
            <tr><td>Crédito a devolver</td><td class="amount">{{ $money($closing['credit']) }}</td></tr>
        @endif
    </tbody></table>

    <p class="thanks">Agradecemos a confiança. Foi um prazer fazer parte da sua celebração.</p>

    <table class="foot"><tbody><tr>
        <td>Fast Bar</td>
        <td class="right">Evento nº {{ $closing['event']['id'] }} · {{ $closing['issued_at'] }}</td>
    </tr></tbody></table>
</body>

</html>
