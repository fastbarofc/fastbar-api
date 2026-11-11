<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilha de Estoque</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 25px;
            color: #333;
            font-size: 12px;
        }

        .header {
            height: 100px;
            width: 100%;
            overflow: hidden;
            text-align: center;
            margin-bottom: 15px;
            position: relative;
        }

        .logo-left {
            width: 100px;
            position: absolute;
            left: 0;
            top: 25px;
        }

        .title {
            display: inline-block;
            margin: 35px auto;
        }

        .hr-strong {
            border: none;
            border-top: 2px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
        }

        th {
            background-color: #f2f2f2;
        }

        tr {
            page-break-inside: avoid;
        }

        .group-row td {
            background-color: #e6e6e6;
            font-weight: bold;
            text-transform: uppercase;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .blank {
            min-width: 60px;
        }

        .text-bold {
            font-weight: bold;
        }

        .muted {
            color: #777;
            font-size: 10px;
            margin-top: 8px;
        }
    </style>
</head>

@php
    $quantity = fn ($value) => $value === null ? '' : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    $money = fn ($value) => $value === null ? '' : 'R$ ' . number_format($value, 2, ',', '.');
    $returned = $stock->isReturned();
@endphp

<body>
    <div class="header">
        <img class="logo-left" src="{{ $logo }}" alt="Fast Bar">
        <h3 class="title">PLANILHA DE ESTOQUE DO EVENTO</h3>
    </div>
    <hr class="hr-strong">

    <table>
        <tr>
            <td colspan="2"><span class="text-bold">EVENTO:</span> {{ $event->name }}</td>
            <td><span class="text-bold">ID:</span> {{ $event->id }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">DATA:</span> {{ $event->date?->format('d/m/Y') }}</td>
            <td><span class="text-bold">Nº CONVIDADOS:</span> {{ $stock->guests }}</td>
            <td><span class="text-bold">LOCAL:</span> {{ $event->eventPlace?->name }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">SAÍDA CONFIRMADA:</span> {{ $stock->sent_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }} {{ $stock->sender?->name ? 'por ' . $stock->sender->name : '' }}</td>
            <td><span class="text-bold">RESPONSÁVEL PELO RETORNO:</span></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>PRODUTO</th>
                <th>CONSUMO / CONV.</th>
                <th>VOLUME</th>
                <th>PREVISTO</th>
                <th>ENVIADO</th>
                <th>RETORNO</th>
                <th>CONSUMO EXTRA</th>
                <th>CONSUMIDO</th>
                <th>VALOR UNIT.</th>
                <th>VALOR GASTO</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group => $items)
                <tr class="group-row">
                    <td colspan="10">{{ $group }}</td>
                </tr>
                @foreach ($items as $item)
                    @php
                        $consumed = $returned ? $item->consumedQuantity(true) : null;
                        $average = $averages[$item->product_id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $item->product?->name }}</td>
                        <td class="number">{{ $item->product?->consumption_per_guest ? rtrim(rtrim(number_format($item->product->consumption_per_guest, 5, ',', '.'), '0'), ',') : '' }}</td>
                        <td class="number">{{ $item->product?->volume ? rtrim(rtrim(number_format($item->product->volume, 3, ',', '.'), '0'), ',') : '' }}</td>
                        <td class="number">{{ $quantity($item->expected_quantity) }}</td>
                        <td class="number text-bold">{{ $quantity($item->sent_quantity) }}</td>
                        <td class="number blank">{{ $returned ? $quantity($item->returned_quantity ?? 0) : '' }}</td>
                        <td class="number blank">{{ $returned ? $quantity($item->extra_quantity) : '' }}</td>
                        <td class="number blank">{{ $quantity($consumed) }}</td>
                        <td class="number">{{ $money($average) }}</td>
                        <td class="number">{{ $consumed !== null && $average !== null ? $money($consumed * $average) : '' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        @if ($consumedValue !== null)
            <tfoot>
                <tr>
                    <td colspan="9" class="text-bold" style="text-align: right;">TOTAL GASTO</td>
                    <td class="number text-bold">{{ $money($consumedValue) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
    <p class="muted">Quantidades em embalagens (garrafa, caixa, pacote). Volume = conteúdo da embalagem. Valor unitário = preço médio das compras.</p>
</body>

</html>
