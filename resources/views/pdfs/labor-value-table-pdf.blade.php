<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tabela de valores</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; color: #2f2b26; font-size: 9px; }
        table.header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #aa8534; margin-bottom: 6px; }
        table.header td { padding: 0 0 4px; vertical-align: middle; }
        table.header td.logo { text-align: right; width: 90px; }
        table.header img { height: 42px; }
        h1 { font-size: 13px; margin: 0; letter-spacing: 1px; }
        .sub { font-size: 8.5px; color: #6c665c; }
        table.vt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.vt th, table.vt td { border: 1px solid #b9ad94; padding: 2px 3px; vertical-align: middle; }
        .title-left { background: #2f2b26; color: #fff; font-size: 10px; text-align: center; padding: 4px; }
        .title-right { background: #aa8534; color: #fff; font-size: 10px; text-align: center; padding: 4px; }
        .col-head { background: #f6f0e2; text-align: center; font-weight: bold; }
        .cities { text-align: center; font-size: 7.5px; line-height: 1.25; vertical-align: bottom; }
        .cities .city { display: block; padding: 1px 2px; }
        .cities .pink { background: #f7d6e6; border-radius: 3px; }
        .cities .eight { background: #fbf7ec; }
        .section { background: #f6f0e2; color: #8e6f2b; font-weight: bold; font-size: 9px; padding: 3px; }
        .row-name { font-weight: bold; width: 120px; }
        td.val { text-align: right; white-space: nowrap; }
        td.eight { background: #fdfbf4; }
        .note { font-size: 8px; color: #6c665c; margin: 5px 0; }
        table.jf { border-collapse: collapse; margin-top: 6px; width: 260px; }
        table.jf th, table.jf td { border: 1px solid #b9ad94; padding: 2px 4px; font-size: 8.5px; }
        table.jf th { background: #f6f0e2; }
        table.jf td.val { text-align: right; }
        .legend { font-size: 8px; color: #6c665c; margin-top: 4px; }
        .sw { display: inline-block; width: 9px; height: 9px; vertical-align: middle; margin-right: 3px; border: 1px solid #b9ad94; }
    </style>
</head>
<body>
    @php $fmt = fn ($v) => $v === null || $v === '' ? '-' : 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp
    <table class="header">
        <tr>
            <td>
                <h1>TABELA DE VALORES DA MÃO DE OBRA</h1>
                <div class="sub">
                    @if ($version['current']) Tabela em uso @else Tabela antiga @endif · salva em {{ \Carbon\Carbon::parse($version['created_at'])->format('d/m/Y H:i') }}@if ($version['user_name']) por {{ $version['user_name'] }}@endif · impresso em {{ $date }}
                </div>
            </td>
            <td class="logo"><b style="font-size:20px;">Fastbar</b></td>
        </tr>
    </table>

    <table class="vt">
        <tr>
            <th class="row-name" style="background:#fff"></th>
            <th class="title-left" colspan="{{ $sixCount }}">{{ $data['title_left'] ?? '' }}</th>
            @if ($eightCount > 0)
                <th class="title-right" colspan="{{ $eightCount }}">{{ $data['title_right'] ?? '' }}</th>
            @endif
        </tr>
        <tr>
            <th class="col-head"></th>
            @foreach ($columns as $c)
                <th class="col-head {{ ($c['hours'] ?? 6) == 8 ? 'eight' : '' }}">{{ $c['label'] }}</th>
            @endforeach
        </tr>
        <tr>
            <td class="row-name" style="text-align:center;color:#8e6f2b">CIDADES</td>
            @foreach ($columns as $c)
                <td class="cities {{ ($c['hours'] ?? 6) == 8 ? 'eight' : '' }}">
                    @foreach ($c['cities'] ?? [] as $city)
                        <span class="city {{ !empty($city['pink']) ? 'pink' : '' }}">{{ $city['name'] }}</span>
                    @endforeach
                </td>
            @endforeach
        </tr>
        @foreach ($data['sections'] ?? [] as $section)
            <tr><td class="section" colspan="{{ count($columns) + 1 }}">{{ $section['name'] ?? '' }}</td></tr>
            @foreach ($section['rows'] ?? [] as $row)
                <tr>
                    <td class="row-name">{{ $row['name'] ?? '' }}</td>
                    @php $i = 0; @endphp
                    @foreach ($row['cells'] ?? [] as $cell)
                        @php $span = max(1, (int) ($cell['span'] ?? 1)); $isEight = ($columns[$i]['hours'] ?? 6) == 8; $i += $span; @endphp
                        <td class="val {{ $isEight ? 'eight' : '' }}" colspan="{{ $span }}" style="text-align:{{ $span > 1 ? 'center' : 'right' }}">{{ $fmt($cell['value'] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach
    </table>

    @if (!empty($data['note']))
        <div class="note">{{ $data['note'] }}</div>
    @endif
    <div class="legend"><span class="sw" style="background:#f7d6e6"></span>Cidade fora do KM padrão (condição diferenciada) &nbsp; <span class="sw" style="background:#fbf7ec"></span>Eventos de 8 horas</div>

    @if (!empty($data['jf']['rows']))
        <table class="jf">
            <tr><th colspan="2">{{ $data['jf']['title'] ?? '' }}</th></tr>
            <tr>
                @foreach ($data['jf']['header'] ?? ['Cidades', 'Valores'] as $h)<th>{{ $h }}</th>@endforeach
            </tr>
            @foreach ($data['jf']['rows'] as $r)
                <tr><td>{{ $r['city'] ?? '' }}</td><td class="val">{{ $fmt($r['value'] ?? null) }}</td></tr>
            @endforeach
        </table>
    @endif
</body>
</html>
