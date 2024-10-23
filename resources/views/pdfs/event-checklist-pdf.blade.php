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

        .logo-left,
        .logo-right {
            width: 100px;
            position: absolute;
        }

        .logo-left {
            left: 0;
        }

        .logo-right {
            right: 0;
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
    </style>
</head>

<body>
    <div class="header">
        <svg class="logo-right" width="31mm" height="23mm" viewBox="0 0 3100 2300" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" stroke-width="28.222" stroke-linejoin="round" xml:space="preserve">
            <image x="0" y="0" width="3100" height="2320" preserveAspectRatio="none" xlink:href="data:image/png;base64,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" />
        </svg>
        <h3 class="text-center">EVT - CHECK LIST DE EVENTO</h3>
        <svg class="logo-left" width="31mm" height="23mm" viewBox="0 0 3100 2300" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" stroke-width="28.222" stroke-linejoin="round" xml:space="preserve">
            <image x="0" y="0" width="3100" height="2320" preserveAspectRatio="none" xlink:href="data:image/png;base64,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" />
        </svg>
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
            <td><span class="text-bold">DATA:</span>&nbsp;{{ $event->date_time->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">HORÁRIO DO
                    DURAÇÃO:</span>&nbsp;{{ $event->date_time->format('H:i') }}h</td>
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
            <td colspan="2"><span class="text-bold">LOCAL:</span>&nbsp;{{ $place->name }}</td>
            <td><span class="text-bold">RESP. LOCAL:</span>&nbsp;{{ $place->responsible }}</td>
            <td><span class="text-bold">TELEFONE:</span>&nbsp;{{ $place->phone_1 }}</td>
        </tr>
        <tr>
            <td colspan="4"><span class="text-bold">ENDEREÇO:</span>&nbsp;{{ $place->address->street }},&nbsp;{{ $place->address->number }},&nbsp;{{ $place->address->city->name }},&nbsp;{{ $place->address->state->letter }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="text-bold">DECORADOR(A):</span>&nbsp;{{ $event->decorator->name }}</td>
            <td colspan="2"><span class="text-bold">CERIMONIAL:</span>&nbsp;{{ $event->ceremonial->name }}</td>
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
            <td> <span class="text-bold">NOME / RAZÃO SOCIAL:</span>&nbsp;{{ $event->invoiceClient->name }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">ENDEREÇO:</span>&nbsp;{{ $event->invoiceClient->address->street }},&nbsp;{{ $event->invoiceClient->address->number }},&nbsp;{{ $event->invoiceClient->address->city->name }},&nbsp;{{ $event->invoiceClient->address->state->letter }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">CPF / CNPJ: </span>{{ $event->invoiceClient->cnpj ?: $event->invoiceClient->cpf }}</td>
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

</body>

</html>