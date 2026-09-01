@php
    /**
     * Proposta comercial Fast Bar.
     *
     * As paginas 2, 3, 4, 5 e 7 sao arte fixa: entram como imagem achatada.
     * Apenas as paginas 1 e 6 recebem conteudo do evento, desenhado sobre o
     * fundo limpo exportado da arte original.
     */
    /**
     * As imagens vao embutidas em base64: o wkhtmltopdf 0.12.6+ bloqueia
     * file:// por padrao e a flag --enable-local-file-access nao existe na
     * 0.12.4. Data URI funciona igual nas duas versoes, sem flag.
     */
    $art = function (string $file) {
        return 'data:image/jpeg;base64,' . base64_encode(file_get_contents(public_path("img/proposals/fastbar/{$file}")));
    };

    $money = function ($value) {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    };
@endphp
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Proposta Comercial - {{ $proposal['event_name'] }}</title>

    <style>
        /* wkhtmltopdf usa as opcoes de linha de comando; isso alinha a
           previsualizacao em navegador com o formato final. */
        @page {
            size: 297mm 210mm;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background-color: #000000;
        }

        body {
            font-family: 'Arial', Helvetica, sans-serif;
            color: #ffffff;
        }

        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
            page-break-after: always;
        }

        .page-last {
            page-break-after: auto;
        }

        .page-art {
            position: absolute;
            top: 0;
            left: 0;
            width: 297mm;
            height: 210mm;
        }

        /* ----- pagina 1: capa ----- */

        .cover-info {
            position: absolute;
            left: 6.9%;
            top: 65.2%;
            width: 46%;
        }

        .cover-number {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1.2px;
            margin: 0;
        }

        .cover-validity {
            font-size: 12px;
            letter-spacing: 2.4px;
            color: #b6b9c2;
            margin: 4px 0 0 0;
        }

        .cover-fields {
            margin-top: 24px;
        }

        .cover-field {
            font-size: 15px;
            line-height: 1.55;
            margin: 0;
        }

        .cover-field .label {
            font-weight: bold;
            letter-spacing: 0.8px;
        }

        /* ----- pagina 6: orcamento ----- */

        .detail-content {
            position: absolute;
            left: 6.6%;
            top: 27.5%;
            width: 84%;
        }

        .detail-cols {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-cols td {
            vertical-align: top;
            padding: 0;
        }

        .detail-cols td.col-left {
            width: 53%;
            padding-right: 5%;
        }

        .detail-cols td.col-right {
            width: 42%;
        }

        h2 {
            font-size: 15px;
            font-weight: normal;
            letter-spacing: 0.4px;
            margin: 0 0 8px 0;
        }

        h2.spaced {
            margin-top: 14px;
        }

        ul {
            margin: 0;
            padding-left: 19px;
        }

        li {
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 5px;
        }

        li span {
            color: #ffffff;
        }

        ul.pink li {
            color: #ec1c6e;
        }

        ul.blue li {
            color: #27aae1;
        }

        ul.tight li {
            margin-bottom: 1px;
        }

        p.line {
            font-size: 13px;
            line-height: 1.45;
            margin: 6px 0 0 0;
        }

        p.note {
            font-size: 13px;
            line-height: 1.45;
            margin: 12px 0 0 0;
        }
    </style>
</head>

<body>
    {{-- Pagina 1: capa com os dados do evento --}}
    <div class="page">
        <img class="page-art" src="{{ $art('page-1-bg.jpg') }}" alt="">
        <div class="cover-info">
            <p class="cover-number">PROPOSTA N°{{ $proposal['proposal_number'] }}</p>
            <p class="cover-validity">VALIDADE {{ $proposal['validity_days'] }} DIAS</p>

            <div class="cover-fields">
                <p class="cover-field"><span class="label">Cliente:</span> {{ $proposal['client_name'] }}</p>
                <p class="cover-field"><span class="label">Evento:</span> {{ $proposal['event_name'] }}</p>
                <p class="cover-field"><span class="label">Local:</span> {{ $proposal['location'] }}</p>
                <p class="cover-field"><span class="label">Data:</span> {{ $proposal['date'] }}</p>
                <p class="cover-field"><span class="label">Duração:</span> {{ $proposal['duration'] }}</p>
                <p class="cover-field"><span class="label">N° de Convidados:</span> {{ $proposal['guests'] }}</p>
            </div>
        </div>
    </div>

    {{-- Paginas 2 a 5: arte institucional fixa --}}
    <div class="page"><img class="page-art" src="{{ $art('page-2.jpg') }}" alt=""></div>
    <div class="page"><img class="page-art" src="{{ $art('page-3.jpg') }}" alt=""></div>
    <div class="page"><img class="page-art" src="{{ $art('page-4.jpg') }}" alt=""></div>
    <div class="page"><img class="page-art" src="{{ $art('page-5.jpg') }}" alt=""></div>

    {{-- Pagina 6: orcamento detalhado --}}
    <div class="page">
        <img class="page-art" src="{{ $art('page-6-bg.jpg') }}" alt="">
        <div class="detail-content">
            <table class="detail-cols">
                <tr>
                    <td class="col-left">
                        <h2>ORÇAMENTO DETALHADO:</h2>
                        <ul class="pink">
                            @foreach ($proposal['budget_details'] as $item)
                                <li><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>

                        @if (!empty($proposal['observations']))
                            <h2 class="spaced">OBSERVAÇÕES:</h2>
                            <ul class="blue">
                                @foreach ($proposal['observations'] as $item)
                                    <li><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </td>

                    <td class="col-right">
                        <h2>RESUMO:</h2>
                        <h2>BAR DE DRINKS:</h2>
                        <ul class="pink tight">
                            @foreach ($proposal['drinks_bar_items'] as $item)
                                <li><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                        <p class="line">Valor por pessoa: {{ $money($proposal['drinks_bar_price']) }}.</p>

                        @if (!empty($proposal['optional_drinks_note']))
                            <p class="note">{{ $proposal['optional_drinks_note'] }}</p>
                        @endif

                        <h2 class="spaced">BAR DE DESTILADOS:</h2>
                        <ul class="pink tight">
                            @foreach ($proposal['spirits_bar_items'] as $item)
                                <li><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                        <p class="line">Valor por pessoa: ({{ $money($proposal['spirits_bar_price']) }}):</p>

                        @if (!empty($proposal['additional_items']))
                            <h2 class="spaced">ADICIONAIS (troca):</h2>
                            <ul class="pink tight">
                                @foreach ($proposal['additional_items'] as $item)
                                    <li><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Pagina 7: encerramento --}}
    <div class="page page-last"><img class="page-art" src="{{ $art('page-7.jpg') }}" alt=""></div>
</body>

</html>
