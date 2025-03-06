<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Evento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }

        p {
            font-size: 26px;
            margin-top: 17px;
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
    </style>
</head>

<body>
    <div class="container">
        <p><strong>Evento:</strong> {{ $event->name }}</p>
        <p><strong>Local:</strong> {{ $place?->name }}</p>
        <p><strong>Endereço:</strong> {{ $place?->address?->street }}, {{ $place?->address?->number }} - {{ $place?->address?->district }} - {{ $place?->address?->city->name }} - {{ $place?->address?->state->letter }}</p>
        <p><strong>Data:</strong> {{ $date }}</p>
        <p><strong>Horário: </strong>{{ $event->time ? \Carbon\Carbon::parse($event->time)->format('H:i') : 'Hora a definir' }} </p>
        <p><strong>Duração (h):</strong> {{ $event->duration }}</p>
        <p><strong>Total de Convidados:</strong> {{ $totalGuests }}</p>
        <p><strong>Responsável:</strong> {{ $event->collaborator->name }}</p>

        <div style="margin-top: 15px;">
            @foreach($kits as $kit)
            @foreach($kit->children as $child)
            <div style="margin-bottom: 15px;">
                <h2 style="font-weight: normal; margin-bottom: 0px;">{{ $child['data']['name'] }}</h2>
                <hr class="hr-strong" style="margin-top: 0px !important;">
                <div style="margin-top: 15px;">
                    @if(!empty($child['children']))
                    @php
                    $child2 = App\Models\EventKit::find($child['data']['id']);
                    $subChildren = $child2?->children ?? [];
                    @endphp
                    @if (!empty($subChildren))
                    @if ($subChildren[0]['data']['type'] == 'kit')
                    @foreach($subChildren as $subChild)
                    @php
                    $component = 'budget-kits';
                    @endphp
                    @include('pdfs.' . $component, $subChild)
                    @endforeach
                    @else
                    @php
                    $component = 'budget-products';
                    $nestedChildren = $subChildren;
                    @endphp
                    @include('pdfs.' . $component, $nestedChildren)
                    @endif
                    @endif
                    @endif
                </div>
            </div>
            @endforeach
            @endforeach
        </div>
    </div>
</body>

</html>