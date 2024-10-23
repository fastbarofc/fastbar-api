<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo - Buffet</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin-left: 100px;
            margin-right: 100px;
            margin-top: 50px;
        }
        

        .text-bold {
            font-weight: bold;
        }

        .header {
            width: 100%;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th, td {
            padding: 8px 12px;
            border-bottom: 1px solid #333;
            text-align: left;
        }

        td:first-child {
        width: 75%;
    }

    td:last-child {
        width: 25%;
    }
    </style>
</head>

<body>
    <div class="header">
        <h3>RESUMO DE EVENTO</h3>
    </div>
    <div>
        <p><span class="text-bold">EVENTO:</span> {{ $event->name }}</p>
        <p><span class="text-bold">RESPONSÁVEL:</span> {{ $responsibles }}</p>
        <p><span class="text-bold">CÓDIGO:</span> {{ $event->id }}</p>
        <p><span class="text-bold">DATA:</span> {{ $event->date_time->format('d/m/Y') }}</p>
        <p><span class="text-bold">LOCAL:</span> {{ $event->eventPlace->description }}</p>
        <p><span class="text-bold">HORÁRIO:</span> {{ $event->date_time->format('H:i') }} h</p>
        <p><span class="text-bold">DURAÇÃO:</span> {{ $event->duration }} h</p>
        <p><span class="text-bold">NÚMERO DE CONVIDADOS:</span> {{ $totalGuests }}</p>
    </div>
    <div>
    <h3>CARDÁPIO:</h3>
    <ul>
    @foreach($kits as $kit)
        <li style="margin-bottom: 5px;">{{ $kit->name }}
            <ul>
            @foreach($kit->children as $child)
            <li style="margin-bottom: 5px;">{{ $child['data']['name'] }}
                @if(!empty($child['children']))
                    <ul>
                        @php
                            $kitLevel2 = App\Models\EventKit::find($child['data']['id']);
                            $childrenKitLevel2 = $kitLevel2->children;
                        @endphp
                        @if(!empty($childrenKitLevel2))
                        @foreach($childrenKitLevel2 as $product)
                        @if ($loop->index % 2 == 0)
                        <li style="margin-bottom: 5px;">{{ $product['data']['name'] }}</li>
                        @endif
                        @endforeach
                        @endif
                    </ul>
                    @endif
                </li>
            @endforeach
            </ul>
        </li>
    @endforeach
    </ul>
</div>

    <table>
        <tr>
            <td><span class="text-bold">Material</span></td>
            <td><span class="text-bold">Qtd.</span></td>
        </tr>
        @foreach($selectedMaterials as $material)
        <tr>
            <td>{{ $material->description }}</td>
            <td>{{ $material->quantity }}</td>
        </tr>
        @endforeach
    </table>
</body>

</html>