@php
    /**
     * Proposta comercial Fast Bar.
     *
     * As paginas 2, 3, 4, 5 e a de encerramento sao arte fixa: entram como
     * imagem achatada. Apenas a capa e as paginas de orcamento recebem
     * conteudo do evento, desenhado sobre o fundo limpo exportado da arte
     * original.
     *
     * O orcamento pode ocupar mais de uma pagina: EventProposalService::pages()
     * ja entrega os blocos distribuidos, sem nenhum partido no meio.
     */
    /**
     * As imagens vao embutidas em base64: o wkhtmltopdf 0.12.6+ bloqueia
     * file:// por padrao e a flag --enable-local-file-access nao existe na
     * 0.12.4. Data URI funciona igual nas duas versoes, sem flag.
     */
    $art = function (string $file) {
        return 'data:image/jpeg;base64,' . base64_encode(file_get_contents(public_path("img/proposals/fastbar/{$file}")));
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

        /* ----- capa ----- */

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

        /* ----- paginas de orcamento ----- */

        .detail-content {
            position: absolute;
            left: 6.6%;
            top: 27.5%;
            width: 84%;
        }

        /* Colunas em absoluto: a esquerda pode esvaziar nas paginas seguintes
           sem que a direita mude de lugar. */
        .detail-col {
            position: absolute;
            top: 0;
        }

        .detail-col-left {
            left: 0;
            width: 53%;
        }

        .detail-col-right {
            left: 58%;
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
    </style>
</head>

<body>
    {{-- Capa com os dados do evento --}}
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

    {{-- Arte institucional fixa --}}
    <div class="page"><img class="page-art" src="{{ $art('page-2.jpg') }}" alt=""></div>
    <div class="page"><img class="page-art" src="{{ $art('page-3.jpg') }}" alt=""></div>
    <div class="page"><img class="page-art" src="{{ $art('page-4.jpg') }}" alt=""></div>
    <div class="page"><img class="page-art" src="{{ $art('page-5.jpg') }}" alt=""></div>

    {{-- Orcamento detalhado: uma ou mais paginas, conforme o que couber --}}
    @foreach ($pages as $page)
        <div class="page">
            <img class="page-art" src="{{ $art('page-6-bg.jpg') }}" alt="">
            <div class="detail-content">
                <div class="detail-col detail-col-left">
                    @foreach ($page['left'] as $index => $block)
                        @include('pdfs.proposal-block', ['block' => $block, 'first' => $index === 0])
                    @endforeach
                </div>

                <div class="detail-col detail-col-right">
                    @if (!empty($page['right']))
                        <h2>RESUMO:</h2>

                        @foreach ($page['right'] as $index => $block)
                            @include('pdfs.proposal-block', ['block' => $block, 'first' => $index === 0])
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    {{-- Encerramento --}}
    <div class="page page-last"><img class="page-art" src="{{ $art('page-7.jpg') }}" alt=""></div>
</body>

</html>
