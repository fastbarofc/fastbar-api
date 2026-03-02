<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Evento</title>
    <style>
        /* Layout */
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            color: #222;
            margin: 18px;
            padding: 18px;
            font-size: 13px;
            line-height: 1.35;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
        }

        /* Header */
        .event-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .event-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: #333;
        }

        .event-sub {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
        }

        /* Event details grid */
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 18px;
            margin-top: 12px;
        }

        .details p {
            margin: 2px 0;
            font-size: 13px;
            color: #222;
        }

        .label {
            font-weight: 700;
            color: #3a3a3a;
        }

        /* Kits / sections */
        .section {
            margin-top: 14px;
        }

        .kit {
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            background: #fafafa;
        }

        .kit h2 {
            font-size: 15px;
            margin: 0 0 6px 0;
            font-weight: 600;
            color: #2c2c2c;
        }

        .hr-strong {
            border: none;
            border-top: 2px solid #aa8534;
            margin: 8px 0;
        }

        hr {
            border: none;
            border-top: 1px solid #e6d7c1;
            margin: 10px 0;
        }

        /* Small helper for long addresses */
        .muted {
            color: #555;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="event-header">
            <h1 class="event-title">Evento: {{ $event->name }}</h1>
            <div class="event-sub">{{ $place?->name ?? '' }}</div>
        </div>

        <div class="details" aria-label="detalhes do evento">
            <p><span class="label">Endereço:</span> <span class="muted">{{ $place?->address?->street }}, {{ $place?->address?->number }} - {{ $place?->address?->district }} - {{ $place?->address?->city->name }} - {{ $place?->address?->state->letter }}</span></p>
            <p><span class="label">Data:</span> {{ $date }}</p>
            <p><span class="label">Horário:</span> {{ $event->time ? \Carbon\Carbon::parse($event->time)->format('H:i') : 'Hora a definir' }}</p>
            <p><span class="label">Duração (h):</span> {{ $event->duration }}</p>
            <p><span class="label">Total de Convidados:</span> {{ $totalGuests }}</p>
            <p><span class="label">Responsável:</span> {{ $event->collaborator->name }}</p>
        </div>

        <div class="section">
            @foreach($kits as $kit)
                @php $showObservations = $showObservations ?? false; @endphp
                @foreach($kit->children as $child)
                    <div class="kit">
                        <h2>{{ $child['data']['name'] }}</h2>
                        @if(isset($showObservations) && $showObservations && !empty($child['data']['observation']))
                            <p style="font-size: 11px; color: #555; margin: 4px 0 0 0;">Obs: {{ $child['data']['observation'] }}</p>
                        @endif
                        <hr class="hr-strong">
                        <div>
                            @if(!empty($child['children']))
                                @php
                                    $child2 = App\Models\EventKit::find($child['data']['id']);
                                    $subChildren = $child2?->children ?? [];
                                @endphp
                                @if (!empty($subChildren) && !empty($subChildren[0]))
                                    @if ($subChildren[0]['data']['type'] == 'kit')
                                        @foreach($subChildren as $subChild)
                                            @include('pdfs.budget-kits', ['subChild' => $subChild, 'showObservations' => $showObservations ?? false])
                                        @endforeach
                                    @else
                                        @include('pdfs.budget-products', ['nestedChildren' => $subChildren, 'showObservations' => $showObservations ?? false])
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