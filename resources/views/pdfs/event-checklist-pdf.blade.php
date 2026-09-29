<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Evento</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&family=Lato&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 25px;
            color: #333;
        }

        .header {
            height: 100px;
            width: 100%;
            overflow: hidden;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
        }

        .logo-left {
            width: 100px;
            position: absolute;
            left: 0;
            top: 25px;
        }

        .text-center {
            display: inline-block;
            margin: 30px auto;
            max-width: calc(100% - 200px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hr-strong {
            border: none;
            border-top: 2px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #333;
        }

        th,
        td {
            padding: 10px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-bold {
            font-weight: bold;
        }

        .composition-row td {
            padding-top: 4px;
            padding-bottom: 4px;
            font-size: 13px;
            color: #555;
        }

        .composition-item {
            padding-left: 35px;
        }

        .composition-quantity {
            color: #888;
            margin-left: 6px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img class="logo-left" src="{{ $logo }}" alt="Fast Bar">
        <h3 class="text-center">EVT - CHECK LIST DE EVENTO</h3>
    </div>
    <hr class="hr-strong">
    <div style="overflow: hidden; padding: 5px 25px;">
        <span style="float: left;">
            <p class="text-bold" style="display: inline;">RESPONSÁVEL(IS):</p>&nbsp;
            {{ $event->name }}
        </span>
        <span style="float: right;">
            <p class="text-bold" style="display: inline;">ID:</p>&nbsp;
            {{ $event->id }}
        </span>
    </div>
    <hr class="hr-strong">
    <table style="page-break-inside: avoid;">
        <tr>
            <th colspan="4">DADOS DO EVENTO</th>
        </tr>
        <tr>
            <td colspan="3"> <span class="text-bold">EVENTO:</span>&nbsp;{{ $event->name }}</td>
            <td><span class="text-bold">DATA:</span>&nbsp;{{ Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">HORÁRIO DO
                    DURAÇÃO:</span>&nbsp;{{ $event->time ? Carbon\Carbon::parse($event->time)->format('H:i') . 'h' : 'Hora a definir' }}h</td>
            <td><span class="text-bold">DURAÇÃO:</span>&nbsp;{{ $event->duration }}h</td>
            <td><span class="text-bold">PÓS EVENTO:</span>&nbsp;{{ $event->duration_after }}h</td>
            <td><span class="text-bold">Nº TOTAL DE
                    CONVIDADOS:</span>&nbsp;{{ $totalGuests }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">CLIENTE:</span>&nbsp;{{ $event->client->name }}</td>
            <td><span class="text-bold">RESP. CONTRATO:</span>&nbsp;{{ $eventResponsibleNames }}</td>
            <td><span class="text-bold">TELEFONE:</span>&nbsp;{{ $event->client->phone_1 }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">LOCAL:</span>&nbsp;{{ $place?->name }}</td>
            <td><span class="text-bold">RESP. LOCAL:</span>&nbsp;{{ $place?->responsible }}</td>
            <td><span class="text-bold">TELEFONE:</span>&nbsp;{{ $place?->phone_1 }}</td>
        </tr>
        <tr>
            <td colspan="4"><span class="text-bold">ENDEREÇO:</span>&nbsp;{{ $place?->address?->street }},&nbsp;{{ $place?->address?->number }},&nbsp;{{ $place?->address?->city->name }},&nbsp;{{ $place?->address?->state->letter }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">DECORADOR(A):</span>&nbsp;{{ $event->decorator?->name }}</td>
            <td colspan="2"><span class="text-bold">CERIMONIAL:</span>&nbsp;{{ $event->ceremonial?->name }}</td>
        </tr>
    </table>

    <table style="page-break-inside: avoid;">
        <tr>
            <th colspan="3">LOGÍSTICA</th>
        </tr>
        <tr>
            <td colspan="3"> <span class="text-bold">MAPA</span></td>
        </tr>
        <tr >
            <td colspan="3" style="height: 20px;"><span class="text-bold"></span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">DATA LIBERAÇÃO DO ESPAÇO:</span></td>
            <td><span class="text-bold">HORÁRIO:</span>&nbsp;&nbsp;&nbsp;h</td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">DATA SAÍDA DOS CAMINHÕES:</span></td>
            <td><span class="text-bold">HORÁRIO:</span>&nbsp;&nbsp;&nbsp;h</td>
        </tr>
        <tr>
            <td colspan="3"><span class="text-bold">OBSERVAÇÕES DE SAÍDA:</span></td>
        </tr>
    </table>

    <table>
        <tr>
            <th>INFORMAÇÕES PARA EMISSÃO DA NF</th>
        </tr>
        <tr>
            <td> <span class="text-bold">NOME / RAZÃO SOCIAL:</span>&nbsp;{{ $event->invoiceClient?->name }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">ENDEREÇO:</span>&nbsp;{{ $event->invoiceClient?->address?->street }},&nbsp;{{ $event->invoiceClient?->address->number }},&nbsp;{{ $event->invoiceClient?->address?->city->name }},&nbsp;{{ $event->invoiceClient?->address->state->letter }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">CPF / CNPJ: </span>{{ maskCnpj($event->invoiceClient?->cnpj) ?: maskCpf($event->invoiceClient?->cpf) }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="5">MESA TEMÁTICA</th>
        </tr>
        <tr>
            <td> <span class="text-bold">Item</span></td>
            <td> <span class="text-bold">Pontos / Metragem Total / Responsável pelos
                    Aparadores</span></td>
        </tr>
        @foreach ($themedTables as $themedTable)
        <tr>
            <td>
                {{ $themedTable->name }}
            </td>
            <td>
                XXXX
            </td>
        </tr>
        @endforeach
    </table>

    <table style="page-break-inside: avoid;">
        <tr>
            <th colspan="5">UTENSÍLIOS E MATERIAIS</th>
        </tr>
        <tr>
            <td><span class="text-bold">DESCRIÇÃO</span></td>
            <td><span class="text-bold">SIM</span></td>
            <td><span class="text-bold">NÃO</span></td>
            <td><span class="text-bold">QTD</span></td>
            <td><span class="text-bold">OBS</span></td>
        </tr>
        @foreach ($event->materials as $material)
        <tr>
            <td>{{ $material->description }}</td>
            <td>{{ $material->pivot->selected ? 'X' : '' }}</td>
            <td>{{ !$material->pivot->selected ? 'X' : '' }}</td>
            <td>{{ $material->pivot->quantity }}</td>
            <td></td>
        </tr>
        @endforeach
    </table>

    <table style="page-break-inside: avoid;">
        <tr>
            <th colspan="2">DRINKS</th>
        </tr>
        <tr>
            <td><span class="text-bold">DRINK</span></td>
            <td><span class="text-bold">OBS</span></td>
        </tr>
        @foreach ($checklistDrinks as $drink)
        <tr>
            <td>{{ $drink->name }}</td>
            <td>{{ $drink->pivot->observation }}</td>
        </tr>
        @foreach ($drink->components as $component)
        <tr class="composition-row">
            <td class="composition-item">
                {{ $component->name }}
                <span class="composition-quantity">{{ rtrim(rtrim(number_format($component->pivot->quantity, 3, ',', '.'), '0'), ',') }} {{ $component->unit?->description }}</span>
            </td>
            <td></td>
        </tr>
        @endforeach
        @endforeach
    </table>

</body>

</html>