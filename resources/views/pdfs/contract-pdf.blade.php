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
        <p> {{ $contract->created_at->format('d/m/Y') }}</p>
    </div>
    <div class="logo-title">
        <h2>CONTRATO</h2>
    </div>
    <table class="event-section">
        <tr class="event-row">
            <td>Evento:</td>
            <td>{{ $contract->properties['name'] }}</td>
            <td>Contato:</td>
            <td>{{ $contract->properties['client']['phone_1'] }}</td>
        </tr>
        <tr class="event-row">
            <td>Data:</td>
            <td>{{ \Carbon\Carbon::parse($contract->properties['date'])->format('d/m/Y') }}</td>
            <td>Local:</td>
            <td>{{ $contract->properties['place']['name'] }}</td>
        </tr>
        <tr class="event-row">
            <td>Horário:</td>
            <td>{{ $contract->properties['time'] ? \Carbon\Carbon::parse($contract->properties['time'])->format('H:i') . ' h' : 'Hora a definir'  }} h</td>
            <td>Duração (h):</td>
            <td>{{ $contract->properties['duration'] }} h</td>
        </tr>
        <tr class="event-row">
            <td>Cliente:</td>
            <td>{{ $contract->properties['client']['name'] }}</td>
            <td style="white-space: nowrap;">N° Convidados:</td>
            <td> {{ $contract->properties['event_guests'] }} </td>
        </tr>
    </table>

    @php
        $mapping = [
    '{{ nome_cliente }}' => $contract->event->client->name ?? 'Não informado',
    '{{ nacionalidade_cliente }}' => $contract->event->client->nationality ?? 'Não informado',
    '{{ estado_civil_cliente }}' => $contract->event->client->civil_status ?? 'Não informado',
    '{{ profissao_cliente }}' => $contract->event->client->profession ?? 'Não informado',
    '{{ cpf_cliente }}' => $contract->event->client->cpf ?? 'Não informado',
    '{{ rg_cliente }}' => $contract->event->client->rg ?? 'Não informado',
    '{{ endereco_cliente.rua }}' => $contract->event->client->address->street ?? 'Não informado',
    '{{ endereco_cliente.numero }}' => $contract->event->client->address->number ?? 'Não informado',
    '{{ endereco_cliente.complemento }}' => $contract->event->client->address->complement ?? '',
    '{{ endereco_cliente.bairro }}' => $contract->event->client->address->district ?? 'Não informado',
    '{{ endereco_cliente.cep }}' => $contract->event->client->address->zip_code ?? 'Não informado',
    '{{ nome_evento }}' => $contract->event->name,
    '{{ local_evento }}' => $contract->event->eventPlace->name ?? 'Local não informado',
    '{{ data_evento }}' => \Carbon\Carbon::parse($contract->event->date)->format('d/m/Y'),
    '{{ horario_evento }}' => $contract->event->time ? \Carbon\Carbon::parse($contract->event->time)->format('H:i') : 'Hora a definir',
    '{{ duracao_evento }}' => $contract->event->duration,
    '{{ numero_convidados }}' => $contract->event->event_guests ?? 'Número de convidados não informado',
    '{{ data_assinatura }}' => \Carbon\Carbon::parse($contract->created_at)->format('d/m/Y'),
];

$contractBody = $contract->body;

foreach ($mapping as $placeholder => $value) {
    $contractBody = str_replace($placeholder, $value, $contractBody);
}
        @endphp

    <div class="event-text">
        <p>{!! $contractBody !!}</p>
    </div>

    <h4>Os itens contratados são:</h4>

    <p><span class="text-bold">Itens de Buffet:</span></p>

    <div class="event-section">
        <ul>
            @foreach ($contract['properties']['event_kits'] as $key => $kit)
            <li>
                {{ $kit['name'] }}
                @if(!empty($kit['children'][0]))
                <ul>
                    @foreach ($kit['children'] as $key1 => $child)
                    <li>
                        {{ $child['name'] }}
                        @if(!empty($child['children'][0]))
                        <ul>
                            @foreach ($child['children'] as $key2 => $child2)
                            <li>
                                {{ $child2['name'] }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
        </ul>
    </div>

    <div class="event-section">
        <span class="text-bold">Materiais:</span>
        <br><br>
        <ul>
            @foreach ($contract['properties']['materials'] as $material)
            <li>{{ $material['name'] }} (Qtd: {{ $material['quantity'] }})</li>
            @endforeach
        </ul>
    </div>

    <div class="event-section">
        <span class="text-bold">Valores:</span>
        <br><br>
        Locação: R$ {{ number_format(floatval($contract->properties['lease'] ?? 0), 2, ',', '.') }}</br>
        Buffet: R$ {{ number_format(floatval($contract->properties['buffet_value'] ?? 0), 2, ',', '.') }}.

    </div>

    <div class="event-section">
        <span class="text-bold">Lotes:</span>
        <br><br>
        <ul>
            @foreach ($contract->properties['batches'] as $batch)
            <li>Lote {{ $loop->index + 1 }}:
                <ul>
                    <li>Valor unitário: R$ {{ number_format(floatval($batch['unit_value'] ?? 0), 2, ',', '.') }} </li>
                    <li>Convidados: {{ number_format(floatval($batch['guests'] ?? 0), 0, ',', '.') }} </li>
                    <li>Total: R$ {{ number_format(floatval($batch['unit_value'] ?? 0) * ($batch['guests'] ?? 0), 2, ',', '.') }} </li>
                </ul>
            </li>
            @endforeach
        </ul>
    </div>

    <div class="event-section">
        <span class="text-bold">Colaboradores:</span>
        <br><br>
        <ul>
            @foreach ($contract->properties['collaborators'] as $collaborator)
            <li>{{ $collaborator['name'] }} (Qtd: {{ $collaborator['quantity'] }})</li>
            @endforeach
        </ul>

    </div>
</body>

</html>