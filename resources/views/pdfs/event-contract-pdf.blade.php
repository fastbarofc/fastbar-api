<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Prestação de Serviços de Coquetelaria</title>

    <style>
        /*
         * As margens da página vêm das opções do wkhtmltopdf, que também
         * reservam o espaço do cabeçalho e do rodapé de marca.
         *
         * A marca d'água é o fundo do body, e não um `position: fixed`:
         * o wkhtmltopdf 0.12.6 desenha elementos fixos só na primeira
         * página, mas repete o fundo em todas. O tile tem a proporção da
         * área de conteúdo (168 x 253mm) com o X centralizado, então basta
         * um tile por página. Ele é opaco de propósito: o Qt suja o canal
         * alfa ao ampliar a imagem em ~5x e deixa um halo cinza em volta
         * dos traços. Como a página é branca, o fundo branco do tile
         * resolve, e é o que a arte original também faz.
         *
         * O período é dado em unidades CSS, que não batem com as físicas:
         * o smart shrinking monta o layout na largura inteira da página
         * (210mm) e reduz para os 168mm de conteúdo, fator 0,8. Logo os
         * 253mm de conteúdo valem 253 / 0,8 = 316,25mm de CSS. Mexer nas
         * margens do PDF exige refazer essa conta e a proporção do tile.
         */
        body {
            margin: 0;
            color: #333;
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            background-image: url('{{ $brand['watermark'] }}');
            background-repeat: repeat-y;
            background-position: left top;
            background-size: 210mm 316.25mm;
        }

        p {
            text-align: justify;
        }

        .header {
            display: -webkit-box;
            -webkit-box-align: center;
            -webkit-box-pack: justify;
            margin-bottom: 20px;
        }

        .contract-title {
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        .clause-number {
            font-weight: bold;
        }

        .clauses h3 {
            font-size: 12px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .clauses table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 20px 0;
        }

        .clauses th,
        .clauses td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .menu-list {
            margin-top: 0;
        }

        .signature {
            margin-top: 50px;
        }

        /* Regra no próprio `p` é necessária: a de cima é herdada e perde
           para o `p { text-align: justify }` das cláusulas. */
        .signature p,
        .witnesses p {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <p><strong>{{ $contract['issued_at']->format('d/m/Y') }}</strong></p>
        <p><strong>Código: {{ $contract['code'] }}</strong></p>
    </div>

    <div class="contract-title">Contrato de Prestação de Serviços de Coquetelaria</div>

    <p>
        O presente instrumento particular de contrato de prestação de serviços de Coquetelaria tem como contratantes as
        seguintes partes:
    </p>

    <p>
        <strong>CONTRATADO:</strong> PINHEIRO E BALTAZAR LTDA, pessoa jurídica de direito privado, inscrita no CNPJ sob o
        n°: 26.282.057/0001-14, nome fantasia Fast Bar, com sede na Rua Attanor Ribeiro, n°: 65, Bairro: Santo Antônio,
        Viçosa – MG, CEP: 36576-048, doravante denominado <strong>CONTRATADO</strong>, e;
    </p>

    @if ($contract['contractant']['is_company'])
        <p>
            <strong>CONTRATANTE:</strong> {{ $contract['contractant']['name'] }}, pessoa jurídica de direito privado,
            inscrita no CNPJ sob o n°: {{ $contract['contractant']['document'] }}, com sede na
            {{ $contract['contractant']['address'] }}{{ $contract['contractant']['email'] ? ', Email: ' . $contract['contractant']['email'] : '' }}, neste ato representada por
            {{ $contract['contractant']['representative']['name'] }}, CPF
            {{ $contract['contractant']['representative']['cpf'] }}, residente e domiciliado na
            {{ $contract['contractant']['representative']['address'] }}, doravante denominado
            <strong>CONTRATANTE</strong>, resolvem assinar o presente CONTRATO, aplicando-se a este suas disposições
            irrestrita e incondicionalmente, conforme cláusulas e condições seguintes:
        </p>
    @else
        <p>
            <strong>CONTRATANTE:</strong> {{ $contract['contractant']['name'] }}, CPF
            {{ $contract['contractant']['document'] }}{{ $contract['contractant']['rg'] ? ', RG ' . $contract['contractant']['rg'] : '' }}{{ $contract['contractant']['email'] ? ', Email: ' . $contract['contractant']['email'] : '' }}
            residente e domiciliado na {{ $contract['contractant']['address'] }} doravante denominado
            <strong>CONTRATANTE</strong>, resolvem assinar o presente CONTRATO, aplicando-se a este suas disposições
            irrestrita e incondicionalmente, conforme cláusulas e condições seguintes:
        </p>
    @endif

    <div class="clauses">{!! $contract['sections']['clauses_before_menu'] !!}</div>

    <p>
        <span class="clause-number">Cláusula 21ª</span> – O cardápio oferecido será composto da forma que segue abaixo:
    </p>
    <p><strong>MODALIDADE ESCOLHIDA: {{ $contract['menu']['modality'] }}</strong></p>
    <ul class="menu-list">
        @foreach ($contract['menu']['items'] as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <div class="clauses">{!! $contract['sections']['clauses_after_menu'] !!}</div>

    <p style="text-align: center; margin-top: 30px;">
        {{ $contract['issued_at']->translatedFormat('l, d \d\e F \d\e Y') }}
    </p>

    <div class="signature">
        <p>_________________________________________</p>
        <p>{{ $contract['contractant']['name'] }}</p>
        @if ($contract['contractant']['is_company'])
            <p>CNPJ: {{ $contract['contractant']['document'] }}</p>
            <p>Representado por: {{ $contract['contractant']['representative']['name'] }}</p>
            <p>CPF: {{ $contract['contractant']['representative']['cpf'] }}</p>
        @else
            <p>CPF: {{ $contract['contractant']['document'] }}</p>
            @if ($contract['contractant']['rg'])
                <p>RG: {{ $contract['contractant']['rg'] }}</p>
            @endif
        @endif
        <p>CONTRATANTE</p>

        <p style="margin-top: 50px;">_________________________________________</p>
        <p>Pinheiro e Baltazar Ltda</p>
        <p>CNPJ: 26.282.057/0001-14</p>
        <p>CONTRATADO</p>
    </div>

    <table class="witnesses" style="width: 100%; margin-top: 65px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <p>________________________________________</p>
                <p>1ª TESTEMUNHA</p>
                <p>CPF:</p>
            </td>
            <td style="width: 50%; text-align: center;">
                <p>________________________________________</p>
                <p>2ª TESTEMUNHA</p>
                <p>CPF:</p>
            </td>
        </tr>
    </table>
</body>

</html>
