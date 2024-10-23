<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&family=Lato&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin-left: 50px;
            margin-right: 50px;
            color: #333;
        }

        .section {
            margin-bottom: 20px;
        }

        .logo-title {
            display: table;
            width: 100%;
            text-align: center;
        }

        h2,
        h3 {
            font-weight: 900;
            margin-bottom: 0px;
            margin-top: 25px;
            padding: 0px;
            text-transform: uppercase;
        }

        .hr-strong {
            border: none;
            border-top: 2px solid #aa8534;
            margin-top: 10px;
        }

        hr {
            border: none;
            border-top: 1px solid #aa8534;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        ul,
        li {
            margin-top: 0;
            margin-bottom: 0;
        }

        .event-section {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .event-section td {
            vertical-align: top;
        }

        .event-row td:nth-child(odd) {
            font-weight: bold;
            text-align: left;
            width: 15%;
        }

        .event-row td:nth-child(even) {
            text-align: left;
            width: 35%;
        }

        .event-section,
        .event-section td {
            border: none;
        }

        .event-section tr {
            margin-bottom: 10px;
        }


        .bg-gold {
            padding: 1px 10px;
            color: white;
            background-color: #aa8534;
        }

        .parthenon-info span {
            display: block;
            margin: 5px 0;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 10px;
        }

        .footer-item {
            display: flex;
            align-items: center;
        }

        .footer-item span {
            margin-left: 8px;
        }

        .footer-component {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }

        .event-text {
            text-align: justify;
        }

        .text-bold {
            font-weight: bold !important;
        }
    </style>
</head>

<body>
    <div style="display: flex; align-items: center; justify-content: space-between">
        <p> {{ $date }}</p>
    </div>
    <div class="logo-title">
        <h2>CONTRATO</h2>
    </div>
    <table class="event-section">
        <tr class="event-row">
            <td>Evento:</td>
            <td>{{ $event['name'] }}</td>
            <td>Contato:</td>
            <td>{{ $client['phone_1'] }}</td>
        </tr>
        <tr class="event-row">
            <td>Data:</td>
            <td>{{ \Carbon\Carbon::parse($event['date_time'])->format('d/m/Y') }}</td>
            <td>Local:</td>
            <td>{{ $place['name'] }}</td>
        </tr>
        <tr class="event-row">
            <td>Horário:</td>
            <td>{{ \Carbon\Carbon::parse($event['date_time'])->format('H:i') }} h</td>
            <td>Duração (h):</td>
            <td>{{ $event['duration'] }} h</td>
        </tr>
        <tr class="event-row">
            <td>Cliente:</td>
            <td>{{ $client['name'] }}</td>
            <td style="white-space: nowrap;">N° Convidados:</td>
            <td>nao tem esse numero asda sdasd as</td>
        </tr>
    </table>

    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>
    <div class="event-text">
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
    </div>

    <div class="event-section">
        <ul>
            @foreach ($kits as $kitName => $children)
            <li>{{ $kitName }}
                <ul>
                    @foreach ($children as $childKey => $child)
                    @if (is_array($child))
                        <li>{{ $child[0] }}</li>
                    @else
                        <li>{{ $childKey }}
                            <ul>
                                @foreach ($child as $product)
                                <li>{{ $product }}</li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @endforeach
                </ul>
            </li>
            @endforeach
        </ul>
    </div>

    <div class="event-section">
        <p><span class="text-bold">Materiais:</span><br>
            @foreach ($materials as $material)
            <span>{{ $material['name'] }}</span>: <span>{{ $material['quantity'] }}</span>{{ $loop->remaining == 1 ? ' e' : (!$loop->last ? ',' : '.') }}
            @endforeach
        </p>
    </div>

    <div class="event-section">
        <p><span class="text-bold">Valores:</span> <br>
            <span>Locação R$ {{ number_format(floatval($event['lease'] ?? 0), 2, ',', '.') }}</span>.
            <span>Buffet R$ {{ number_format(floatval($event['buffet_value'] ?? 0), 2, ',', '.') }}</span>.
        </p>
    </div>

    <div class="event-section">
        <p><span class="text-bold">Lotes:</span> <br>
            @foreach ($batches as $batch)
            <span>Lote {{ $loop->index + 1 }}: Valor unitário: R$ {{ number_format(floatval($batch['unit_value'] ?? 0), 2, ',', '.') }} Convidados: {{ number_format(floatval($batch['guests'] ?? 0), 0, ',', '.') }} Total: R$ {{ number_format(floatval($batch['unit_value'] ?? 0) * ($batch['guests'] ?? 0), 2, ',', '.') }}</span><br>
            @endforeach
        </p>
    </div>

    <div class="event-section">
        <p><span class="text-bold">Colaboradores:</span> <br>
            @foreach ($collaborators as $collaborator)
            <span>{{ $collaborator['name'] }} {{ $collaborator['quantity'] }}</span><br>
            @endforeach
        </p>
    </div>
</body>

</html>