<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumo - Buffet</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&family=Lato&display=swap" rel="stylesheet">

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
            <li>exemplo1
            <ul>
                <li>exemplo2</li>
                <li>exemplo3</li>
            </ul>
            </li>
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