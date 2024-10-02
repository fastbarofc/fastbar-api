<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento - Buffet</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&family=Lato&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin-left: 100px;
            margin-right: 100px;
            margin-top: 0;
        }
        

        .text-bold {
            font-weight: bold;
        }

        .header {
            display: flex;
            justify-content: center;
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
    </style>
</head>

<body>
    <div class="header">
        <h3>FECHAMENTO DE EVENTO</h3>
    </div>
    
    <table>
        <tr>
            <td><span class="text-bold">EVENTO:</span> {{ $event->id }} - {{ $event->name }}</td>
            <td><span class="text-bold">DATA:</span> {{ $event->date_time->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">RESPONSÁVEL:</span> TENHO QUE COLOCAR ALGUNS</td>
            <td><span class="text-bold">HORÁRIO:</span> {{ $event->date_time->format('H:i') }} h</td>
        </tr>
        <tr>
            <td><span class="text-bold">LOCAL:</span> {{ $place->name }}</td>
            <td><span class="text-bold">DURAÇÃO:</span> {{ $event->duration }} h</td>
        </tr>
        <tr>
            <td><span class="text-bold">CONVIDADOS:</span> FAZER SOMATORIO</td>
            <td><span class="text-bold">CORTESIAS:</span> {{ $event->parthenon_courtesies + $event->partner_courtesies }} </td>
        </tr>
    </table>

    <table>
        <tr>
            <td><span class="text-bold">LOCAÇÃO:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">BUFFET:</span></td>
            <td>R$ {{ $event->buffet_value }}</td>
        </tr>
        <!--saber como calcular daqui para baixo-->
        <tr>
            <td><span class="text-bold">ACRÉSCIMO:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">VENDAS EXTRAS:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">QUEBRAS:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">TOTAL:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">TOTAL PAGO:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
        <tr>
            <td><span class="text-bold">TOTAL A PAGAR:</span></td>
            <td>R$ {{ $event->lease }}</td>
        </tr>
    </table>

    <span class="text-bold" style="margin-bottom: 50px;" >PAGAMENTOS:</span>
    <table>
        <tr>
            <td><span class="text-bold">Data</span></td>
            <td><span class="text-bold">Valor</span></td>
            <td><span class="text-bold">Forma</span></td>
            <td><span class="text-bold">Observações</span></td>
        </tr>
        <tr>
            <td><span class="text-bold">Total</span></td>
            <td><span class="text-bold">Resultado total aqui</span></td>
            <td></td>
            <td></td>
        </tr>
    </table>
</body>

</html>