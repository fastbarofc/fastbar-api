<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento - Buffet</title>

    <style>
        body {
            margin-left: 50px;
            margin-right: 50px;
            padding-top: 50px;
            color: #333;
        }

        .section {
            margin-bottom: 20px;
        }

        .logo-title {
            display: -webkit-box;
            -webkit-box-pack: justify;
            -webkit-box-align: end;
        }

        h2,
        h3 {
            font-weight: 900;
            margin-bottom: 0px;
            margin-top: 25px;
            padding: 0px;
            text-transform: uppercase;
        }

        .logo-title img {
            width: 100px;
            height: auto;
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

        ol {
            list-style-type: none;
            padding-left: 20px;
        }

        ol li {
            position: relative;
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            padding-left: 15px;
            margin-bottom: 5px;
        }

        ol li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.3em;
            width: 5px;
            height: 5px;
            background-color: #AA8534;
            border-radius: 50%;
        }

        .table-container {
            padding: 3px;
            background-color: #f2f2f2;
            margin-top: 20px;
        }

        .event-section {
            width: 100%;
            border-collapse: collapse;
        }

        .event-section td {
            vertical-align: top;
        }

        .event-row td:nth-child(odd) {
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

        .parthenon-info {
            display: block;
            margin: 0;
            padding: 0;
        }

        .footer {
            display: block;
            padding: 10px;
            overflow: hidden;
        }

        .parthenon-info {
            display: inline-block;
            vertical-align: top;
            margin-right: 20px;
        }

        .footer-item {
            display: block;
            margin-bottom: 5px;
        }

        .footer-item svg {
            vertical-align: middle;
            margin-right: 5px;
        }

        .logo {
            display: inline-block;
            float: right;
            vertical-align: top;
        }
    </style>
</head>

<body>
    <div style="display: -webkit-box; -webkit-box-align: center; -webkit-box-pack: justify;">
        <p> {{ $date }}</p>
        <p class="bg-gold">ID: {{ $event->id }} </p>
    </div>
    <div class="logo-title">
        <h2 style="font-weight: normal;">PROPOSTA CASA PARTHENON</h2>
        <img src="data:image/png;base64, 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" alt="Logo Casa Parthenon">
    </div>
    <hr class="hr-strong">
    </hr>
    <div class="table-container">
        <table class="event-section">
            <tr class="event-row">
                <td>Evento:</td>
                <td>{{ $event->name }}</td>
                <td>Contato:</td>
                <td>{{ $client->phone_1 }}</td>
            </tr>
            <tr class="event-row">
                <td>Data:</td>
                <td>{{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</td>
                <td>Local:</td>
                <td>{{ $place?->name }}</td>
            </tr>
            <tr class="event-row">
                <td>Horário:</td>
                <td>{{ $event->time ? \Carbon\Carbon::parse($event->time)->format('H:i') : 'Hora a definir' }} </td>
                <td>Duração (h):</td>
                <td>{{ $event->duration }}</td>
            </tr>
            <tr class="event-row">
                <td>Cliente:</td>
                <td>{{ $client->name }}</td>
                <td style="white-space: nowrap;">N° Convidados:</td>
                <td>{{ $totalGuests }}</td>
            </tr>
        </table>
    </div>
    <div class="event-text" style="page-break-inside: avoid;">
        <p>
            O Casa Parthenon traz um novo conceito de culinária para festas e eventos, unindo tradição e inovação.
            Cada prato, dos clássicos reinventados às novas criações, é uma celebração da alta culinária e a
            valorização da nossa identidade gastronômica.
        </p>
        <p>
            A seguir descubra as possibilidades e novidades da nossa cozinha e viva uma experiência única. Preparese para uma jornada gastronômica incomparável.
        </p>
    </div>
    <div class="kits" style="page-break-inside: avoid;">
        @foreach($kits as $kit)
        <div style="margin-bottom: 15px;">
            <h2 style="font-weight: normal; margin-bottom: 0px;">{{ $kit->name }}</h2>
            <hr class="hr-strong" style="margin-top: 0px !important;">
            @foreach($kit->children as $child)
            <div style="margin-top: 15px;">
                @if(!empty($child['children']))
                    <h3 style="font-weight: normal;">&nbsp; &nbsp; &nbsp; {{ $child['data']['name'] }}</h3>
                    <hr style="margin-top: 0px !important;">
                    <div style="width: 100%; display: table;">
                        <div style="display: table-row;">
                            <div style="display: table-cell; width: 48%; padding-right: 2%;">
                                <ul>
                                    @php
                                    $kitLevel2 = App\Models\EventKit::find($child['data']['id']);
                                    $childrenKitLevel2 = $kitLevel2->children;
                                    @endphp

                                    @if(!empty($childrenKitLevel2))
                                        @foreach($childrenKitLevel2 as $product)
                                            @if ($loop->index % 2 == 0)
                                            <li>{{ $product['data']['name'] }}</li>
                                            @endif
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                            <div style="display: table-cell; width: 48%;">
                                <ul>
                                    @php
                                    $kitLevel2 = App\Models\EventKit::find($child['data']['id']);
                                    $childrenKitLevel2 = $kitLevel2->children;
                                    @endphp

                                    @if(!empty($childrenKitLevel2))
                                        @foreach($childrenKitLevel2 as $product)
                                            @if ($loop->index % 2 == 1)
                                            <li>{{ $product['data']['name'] }}</li>
                                            @endif
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                @else
                    <ul>
                        <li>
                            {{ $child['data']['name'] }}
                        </li>
                    </ul>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    <div style="position: relative; height: 100px;">
        <div style="position: absolute; right: 0; bottom: 0;">
            <p style="padding: 0px; margin: 0px; white-space: nowrap;" class="bg-gold">R$ {{ str_replace('.', ',', str_replace(',', '', $event->guest_unit_value))  }} por pessoa</p>
        </div>
    </div>
    <hr class="hr-strong" style="padding: 0px; margin: 0px;">
    <div class="terms" style="padding-top: 30px; padding-bottom: 30px; page-break-inside: avoid;">
        <div class="terms-title" style="overflow: hidden;">
            <p style="float: left; width: 30%; padding: 0px; margin: 0px; text-align: left;">OBSERVAÇÕES:</p>
            <p style="float: left; width: 30%; padding: 0px; margin: 0px; text-align: left;">RESPONSABILIDADES:</p>
            <div style="float: right; width: 40%; padding: 0px; margin: 0px; text-align: right;">
                <p style="margin: 0;">{{ $event->collaborator->name }}</p>
                <p style="margin: 0;">Telefone: {{ $event->collaborator->phone_1 }}</p>
            </div>
        </div>
        <hr style="padding: 1px; margin: 0px;">
        <div class="terms-title" style="position: relative; overflow: visible">
            <ol style="float: left; width: 30%; padding: 10px;">
                <li>O serviço de buffet serve todos os itens do cardápio a vontade dentro do tempo de duração do evento.</li>
                <li>Duração do evento: {{ $event->duration }} horas</li>
                <li>Hora adicional: 10% do valor do evento</li>
                <li>Preço praticado para o mínimo de {{ $event->min_guests }} pessoas</li>
                <li> Não estão inclusos neste orçamento:</li>
                <li>Toalhas de mesa</li>
                <li>Aparadores para mesas temáticas, prato quente, e/ou jantar</li>
            </ol>
            <ol style="float: left; width: 30%; padding: 10px;">
                <li>O não comparecimento do número de convidados não implicará na redução deste orçamento, outrossim, o excedente será corbado por pessoa.</li>
                <li>No caso de haver aumento nos custos das mercadorias e / ou matérias primas para o fornecimento de que ora se trata, os preços serão reajustados pelo índice do IGP -M ou outro índice que venha a substituir.</li>
                <li>Este orçamento é válido por 10 dias, para o número mínimo de {{ $event->min_guests }} convidados e cancela e substitui os anteriores porventura existentes.</li>
            </ol>
            <div style="position: absolute; top: 0; right: 0; width: 40%; text-align: right; padding: 0px; margin: 0px;">
                <p style="padding: 0px; margin: 0px;">Consultor(a) de Vendas</p>
                <svg style="margin: 10px;" width="33mm" height="12mm" viewBox="0 0 3300 1200" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" stroke-width="28.222" stroke-linejoin="round" xml:space="preserve">
                    <image x="0" y="0" width="3320" height="1220" preserveAspectRatio="none" xlink:href="data:image/png;base64,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" />
                </svg>
            </div>
        </div>
    </div>
    
</body>

</html>