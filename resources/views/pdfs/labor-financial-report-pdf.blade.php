<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro de Eventos - Evento #{{ $event->id }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; color: #2f2b26; font-size: 11px; }
        .page-break { page-break-after: always; }
        table { border-collapse: collapse; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .header { width: 100%; border-bottom: 2px solid #aa8534; margin-bottom: 8px; }
        .header td { padding: 0 0 6px; vertical-align: middle; }
        .header td.logo { width: 130px; }
        .header td.logo img { height: 62px; }
        .header h1 { font-size: 17px; margin: 0; letter-spacing: 1px; text-align: center; }
        .header .sub { font-size: 10px; color: #6c665c; text-align: center; margin-top: 2px; }
        .idbox { width: 110px; border: 2px solid #2f2b26; text-align: center; }
        .idbox .lbl { background: #2f2b26; color: #fff; font-weight: bold; font-size: 10px; padding: 2px 0; letter-spacing: 1px; }
        .idbox .val { font-size: 24px; font-weight: bold; padding: 6px 0; }
        .info { width: 100%; margin-bottom: 8px; }
        .info td { border: 1px solid #b9ad94; padding: 6px 8px; font-size: 13px; }
        .info td.k { background: #f6f0e2; font-weight: bold; text-align: right; width: 95px; font-size: 12px; }
        h2 { font-size: 13px; text-align: center; margin: 10px 0 6px; letter-spacing: 0.5px; }
        .decl { font-size: 11px; text-align: justify; margin: 0 0 8px; }
        table.grid { width: 100%; font-size: 12px; margin-bottom: 8px; }
        table.grid th, table.grid td { border: 1px solid #b9ad94; padding: 3px 5px; height: 20px; }
        table.grid th { background: #f6f0e2; font-size: 10.5px; text-transform: uppercase; }
        table.grid td.n { text-align: center; font-weight: bold; width: 26px; }
        table.grid td.num { text-align: right; white-space: nowrap; }
        table.grid td.label { font-weight: bold; font-size: 11.5px; }
        table.grid tr.sector th { background: #2f2b26; color: #fff; font-size: 12.5px; text-align: left; padding: 4px 6px; letter-spacing: 0.5px; }
        table.grid tr.sector th .q { float: right; font-weight: normal; }
        table.grid tr.truckrow td { font-weight: bold; font-size: 12px; padding: 6px 8px; height: 22px; }
        .plate-line { display: inline-block; width: 160px; border-bottom: 1px solid #2f2b26; height: 14px; vertical-align: bottom; }
        table.grid tr.sub td { background: #faf6ec; font-weight: bold; }
        .coord-box { border: 1px solid #b9ad94; padding: 6px 8px; margin-bottom: 0; font-size: 11px; text-align: justify; }
        .coord-box b.t { display: block; text-align: center; font-size: 11px; margin-bottom: 3px; }
        .total { width: 100%; margin: 4px 0 10px; }
        .total td { border: 1px solid #2f2b26; background: #2f2b26; color: #fff; font-weight: bold; padding: 5px 8px; font-size: 12px; }
        .total td.num { text-align: right; width: 140px; }
        .obs { width: 100%; }
        .obs th { background: #c6b994; text-align: left; padding: 4px 6px; border: 1px solid #b9ad94; }
        .obs td { border: 1px solid #b9ad94; height: 70px; }
        tr.sign-row td { height: 30px; }
        td.att { font-size: 9.5px; color: #2e6b3a; padding: 1px 4px; }
        td.att img { height: 24px; max-width: 140px; vertical-align: middle; margin-right: 4px; }
        td.att .att-label { font-weight: bold; vertical-align: middle; }
        .coord-tag { font-size: 10px; font-weight: bold; color: #fff; background: #aa8534; border-radius: 3px; padding: 1px 4px; margin-left: 4px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $opt = fn ($v) => $v ? 'R$ ' . number_format((float) $v, 2, ',', '.') : '';
    $cpf = function ($v) {
        $d = preg_replace('/\D/', '', (string) $v);
        return strlen($d) === 11 ? substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2) : (string) $v;
    };
    $eventDate = $event->date ? \Carbon\Carbon::parse($event->date)->format('d/m/Y') : '';
@endphp



{{-- CAPA: coordenadores --}}
{{-- O título e o texto da declaração dos coordenadores saem no cabeçalho da página 1 (ganha espaço na impressão). --}}
<table class="grid">
    <thead>
        <tr>
            <th style="width:24%">Coordenadores</th>
            <th>Nome</th>
            <th style="width:15%">CPF</th>
            <th style="width:28%">Assinatura</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($coordinators as $c)
            <tr>
                <td class="label">{{ mb_strtoupper($c['label']) }}</td>
                <td>{{ $c['person']['name'] ?? '' }}</td>
                <td>{{ !empty($c['person']['cpf']) ? $cpf($c['person']['cpf']) : '' }}</td>
                <td></td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="page-break"></div>

{{-- EQUIPE: um bloco por setor, linhas pela Quantificação --}}
{{-- A Declaração de Recebimento sai no cabeçalho de todas as páginas da equipe (André 2026-11-06). --}}

@forelse ($sections as $sec)
    <table class="grid">
        <thead>
            <tr class="sector">
                <th colspan="6">{{ mb_strtoupper($sec['name']) }}
                    <span class="q">{{ $sec['quantity'] }} {{ $sec['quantity'] == 1 ? 'pessoa' : 'pessoas' }}@if (!empty($sec['coordinator_quantity'])) + {{ $sec['coordinator_quantity'] }} {{ $sec['coordinator_quantity'] == 1 ? 'coordenador' : 'coordenadores' }}@endif</span></th>
            </tr>
            @if (!empty($sec['has_truck']))
                <tr class="truckrow">
                    <td colspan="6">CAMINHÃO:&nbsp;&nbsp;@foreach (['3/4' => '3/4', 'TOCO' => 'TOCO', 'TRUCADO' => 'TRUCADO'] as $k => $l)( {!! ($sec['truck_size'] ?? null) === $k ? 'X' : '&nbsp;&nbsp;' !!} ) {{ $l }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;@endforeach&nbsp;&nbsp;&nbsp;PLACA: <span class="plate-line"></span></td>
                </tr>
            @endif
            <tr>
                <th style="width:26px">Nº</th>
                <th>Nome</th>
                <th style="width:13%">CPF</th>
                <th style="width:9%">Pós-festa</th>
                <th style="width:10%">Valor</th>
                <th style="width:34%">Assinatura</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $sec['lines']; $i++)
                @php $r = $sec['rows'][$i] ?? null; @endphp
                <tr class="sign-row">
                    <td class="n">{{ $i + 1 }}</td>
                    <td>{{ $r['name'] ?? '' }}@if ($i < ($sec['coord_slots'] ?? 0)) <span class="coord-tag">COORDENADOR</span>@endif</td>
                    <td>{{ $r && $r['cpf'] ? $cpf($r['cpf']) : '' }}</td>
                    <td class="num">{{ $r ? $opt($r['after_party_value']) : '' }}</td>
                    {{-- Valor = diária + hora adicional + coordenação + outros (o pós-festa fica na coluna dele). --}}
                    <td class="num"><b>{{ $r ? $fmt($r['total'] - (float) $r['after_party_value']) : '' }}</b></td>
                    <td class="att">
                        {{-- Presença confirmada no celular: assinatura com o dedo (imagem) ou reconhecimento facial. --}}
                        @if ($r && !empty($r['attendance']))
                            @if (!empty($r['attendance']['signature_path']) && is_file(storage_path('app/public' . $r['attendance']['signature_path'])))
                                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public' . $r['attendance']['signature_path']))) }}" alt="">
                            @endif
                            <span class="att-label">&#10003; {{ $r['attendance']['label'] }}</span>
                        @endif
                    </td>
                </tr>
            @endfor
            <tr class="sub">
                @php $afterParty = collect($sec['rows'])->filter()->sum(fn ($r) => (float) $r['after_party_value']); @endphp
                <td colspan="3" style="text-align:right">Subtotal {{ $sec['name'] }}</td>
                <td class="num">{{ $afterParty ? $fmt($afterParty) : '' }}</td>
                <td class="num">{{ $fmt($sec['total'] - $afterParty) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
@empty
    <p style="text-align:center;color:#6c665c">Nenhum setor com quantidade na Quantificação deste evento.</p>
@endforelse

<table class="total">
    <tr>
        <td>TOTAL DO EVENTO · {{ count($report['rows']) }} colaborador(es)</td>
        <td class="num">{{ $fmt($report['total']) }}</td>
    </tr>
</table>

<table class="obs">
    <tr><th>OBSERVAÇÕES</th></tr>
    <tr><td></td></tr>
</table>
</body>
</html>
