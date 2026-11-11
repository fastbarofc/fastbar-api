<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Planejamento Financeiro</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; color: #222; font-size: 12px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table.sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sheet td { border: 1px solid #d6d6d6; padding: 4px 7px; height: 20px; white-space: nowrap; overflow: hidden; }
        .title td { background: #dcdcdc; color: #2f5597; font-weight: bold; font-size: 15px; text-align: center; border-color: #c9c9c9; }
        .title td.id { text-align: left; }
        td.k { background: #0b6fc4; color: #fff; font-weight: bold; text-align: center; border-color: #fff; }
        tr.gap td { height: 8px; border-left-color: #fff; border-right-color: #fff; }
        tr.head td { background: #0b6fc4; color: #fff; font-weight: bold; text-align: center; border-color: #fff; }
        tr.line td { background: #f6f3f8; }
        tr.line.hl td { background: #ffff00; }
        td.c { text-align: center; }
        .money span.rs { float: left; }
        .money span.v { float: right; }
        tr.total td { border-top: 2px solid #222; border-bottom: 2px solid #222; font-weight: bold; }
        td.qty { background: #bfe0f2; border-left: 2px solid #222; text-align: center; }
        td.tv { background: #f8d7da; color: #d00000; border-right: 2px solid #222; font-size: 13px; }
        td.guests { background: #c6efce; color: #006100; font-size: 13px; }
        td.costs { border: 2px solid #222; }
        .logo img { height: 38px; }
    </style>
</head>
<body>
@php
    $m = fn ($v) => number_format((float) $v, 2, ',', '.');
@endphp
@foreach ($plans as $plan)
    @php $ev = $plan['event']; $extra = collect($plan['extra_lines']); @endphp
    <div class="page">
        <table class="sheet">
            <colgroup><col style="width:14%"><col style="width:44%"><col style="width:21%"><col style="width:21%"></colgroup>
            <tr class="title">
                <td colspan="3">PLANEJAMENTO FINANCEIRO</td>
                <td class="id">ID: {{ $ev['id'] }}</td>
            </tr>
            <tr><td class="k">EVENTO</td><td colspan="3">{{ $ev['name'] }}</td></tr>
            <tr>
                <td class="k">DATA</td><td>{{ $ev['date'] ? \Carbon\Carbon::parse($ev['date'])->format('d/m/Y') : '' }}</td>
                <td class="k">DURAÇÃO</td><td class="c">{{ $ev['duration'] ? rtrim(rtrim(number_format((float) $ev['duration'], 1, ',', ''), '0'), ',') . ' horas' : '' }}</td>
            </tr>
            <tr>
                <td class="k">CIDADE</td><td>{{ $ev['city'] }}</td>
                <td class="k">VALOR PADRÃO</td>
                <td class="money">@if ($plan['standard_value'] !== null)<span class="rs">R$</span><span class="v">{{ $m($plan['standard_value']) }}</span>@endif</td>
            </tr>
            <tr class="gap"><td colspan="4"></td></tr>
            <tr class="head"><td>QNT.</td><td></td><td>V. UNIT.</td><td>V. TOTAL</td></tr>
            @foreach ($plan['lines'] as $l)
                <tr class="line {{ $l['highlight'] ? 'hl' : '' }}">
                    <td class="c">{{ $l['quantity'] }}</td>
                    <td>{{ $l['description'] }}</td>
                    <td class="money">@if ($l['unit_value'] !== null)<span class="rs">R$</span><span class="v">{{ $m($l['unit_value']) }}</span>@endif</td>
                    <td class="money"><span class="rs">R$</span><span class="v">{{ $m($l['total']) }}</span></td>
                </tr>
            @endforeach
            @foreach ($extra as $l)
                <tr class="line {{ !empty($l['highlight']) ? 'hl' : '' }}">
                    <td class="c">{{ rtrim(rtrim(number_format((float) $l['quantity'], 2, ',', ''), '0'), ',') }}</td>
                    <td>{{ $l['description'] }}</td>
                    <td class="money"><span class="rs">R$</span><span class="v">{{ $m($l['unit_value']) }}</span></td>
                    <td class="money"><span class="rs">R$</span><span class="v">{{ $m($l['total']) }}</span></td>
                </tr>
            @endforeach
            <tr class="total">
                <td class="qty">{{ rtrim(rtrim(number_format((float) $plan['total_quantity'], 2, ',', ''), '0'), ',') }}</td>
                <td colspan="2"></td>
                <td class="tv money"><span class="rs">R$</span><span class="v">{{ $m($plan['total_value']) }}</span></td>
            </tr>
            <tr class="gap"><td colspan="4"></td></tr>
            <tr>
                <td class="k">RECEITA</td><td class="c">{{ $plan['revenue'] !== null ? $m($plan['revenue']) : '' }}</td>
                <td class="k">KPI FREELANCER:</td><td class="c">{{ $plan['kpi'] !== null ? number_format($plan['kpi'], 2, ',', '.') . '%' : '' }}</td>
            </tr>
            <tr class="gap"><td colspan="4"></td></tr>
            <tr><td></td><td class="guests">Nº CONVIDADOS: {{ $ev['guests'] !== null ? number_format((float) $ev['guests'], 0, ',', '.') : '' }}</td><td colspan="2"></td></tr>
            <tr class="gap"><td colspan="4"></td></tr>
            <tr>
                <td></td>
                <td colspan="2" class="costs"><b>Custos:</b>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sim ( {!! $plan['costs_confirmed'] === true ? 'X' : '&nbsp;&nbsp;&nbsp;' !!} )
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Não ( {!! $plan['costs_confirmed'] === false ? 'X' : '&nbsp;&nbsp;&nbsp;' !!} )</td>
                <td></td>
            </tr>
        </table>
        <div style="margin-top:6px;font-size:9px;color:#777">Emitido em {{ $date }} · {{ $ev['place'] }}</div>
    </div>
@endforeach
</body>
</html>
