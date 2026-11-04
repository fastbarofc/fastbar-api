<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aditivo ao Contrato de Prestação de Serviços de Coquetelaria</title>

    <style>
        /* Marca d'água igual à do contrato: a conta do background-size está explicada em event-contract-pdf. */
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
            border: 1px solid #333;
            padding: 4px;
            margin-bottom: 25px;
        }

        .clause-number {
            font-weight: bold;
        }

        h3 {
            font-size: 12px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .event-details {
            font-weight: bold;
            margin-left: 20px;
        }

        .event-details p {
            margin: 2px 0;
        }

        .changes > li {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .changes ul {
            font-weight: normal;
        }

        .issued-at {
            text-align: right;
            margin-top: 30px;
        }

        .signature {
            margin-top: 0;
        }

        .signature p {
            text-align: center;
            margin: 2px 0;
        }

        .signature .party {
            page-break-inside: avoid;
            padding-top: 50px;
        }
    </style>
</head>

<body>
    @php($contractant = $document['contractant'])
    {{-- Aditivos gravados antes da lista de representantes guardam só um, em `representative`. --}}
    @php($representatives = $contractant['representatives'] ?? array_filter([$contractant['representative'] ?? null]))
    @php($event = $document['event'])

    <div class="header">
        <p><strong>Aditivo Nº {{ $code }}</strong></p>
        <p><strong>Contrato Nº {{ $document['contract_code'] }}</strong></p>
    </div>

    <div class="contract-title">Aditivo ao Contrato<br>de Prestação de Serviços de Coquetelaria</div>

    <p>
        Pelo presente instrumento particular, as partes a seguir qualificadas celebram o presente Termo Aditivo ao
        Contrato de Prestação de Serviços de Coquetelaria, que se regulará conforme as cláusulas e condições ora
        estipuladas:
    </p>

    <p>
        De um lado, PINHEIRO E BALTAZAR LTDA, pessoa jurídica de direito privado, inscrita no CNPJ sob o n°:
        26.282.057/0001-14, nome fantasia Fast Bar, com sede na Rua Attanor Ribeiro, n°: 65, Bairro: Santo Antônio,
        Viçosa – MG, CEP: 36576-048, doravante denominado <strong>CONTRATADO</strong>, e;
    </p>

    @if ($contractant['is_company'])
        <p>
            De outro lado, <strong>{{ $contractant['name'] }}</strong>, pessoa jurídica de direito privado, inscrita no
            CNPJ sob o n°: {{ $contractant['document'] }}, com sede na {{ $contractant['address'] }}, neste ato
            representada por
            @foreach ($representatives as $representative)
                {{ $representative['name'] }}, CPF {{ $representative['cpf'] }}, residente e domiciliado na
                {{ $representative['address'] }}{{ $loop->last ? ',' : ($loop->remaining === 1 ? '; e por' : ';') }}
            @endforeach
            doravante denominada <strong>CONTRATANTE</strong>,
            resolvem assinar o presente <strong>TERMO ADITIVO</strong>, aplicando-se a este suas disposições irrestrita e
            incondicionalmente, conforme cláusulas e condições seguintes:
        </p>
    @else
        <p>
            De outro lado, <strong>{{ $contractant['name'] }}</strong>, CPF
            {{ $contractant['document'] }}{{ $contractant['rg'] ? ', RG ' . $contractant['rg'] : '' }}, residente e
            domiciliado na {{ $contractant['address'] }}, doravante denominado <strong>CONTRATANTE</strong>, resolvem
            assinar o presente <strong>TERMO ADITIVO</strong>, aplicando-se a este suas disposições irrestrita e
            incondicionalmente, conforme cláusulas e condições seguintes:
        </p>
    @endif

    <p>
        <span class="clause-number">Cláusula 1ª</span> – O presente CONTRATO tem por objeto a prestação dos serviços de
        coquetelaria à <strong>CONTRATANTE</strong> especificamente no evento que se realizará, conforme disposto abaixo:
    </p>

    <div class="event-details">
        @if ($event['name'])
            <p>Evento: {{ $event['name'] }}</p>
        @endif
        @if ($event['place'])
            <p>Local: {{ $event['place'] }}</p>
        @endif
        @if ($event['date'])
            <p>Data: {{ $event['date'] }}</p>
        @endif
        @if ($event['time'])
            <p>Horário: {{ $event['time'] }} horas</p>
        @endif
        @if ($event['duration'])
            <p>Duração: {{ $event['duration'] }} horas</p>
        @endif
        @if ($event['guests'])
            <p>Número de convidados: {{ $event['guests'] }}</p>
        @endif
    </div>

    <h3>DAS ALTERAÇÕES</h3>

    <p><span class="clause-number">Cláusula 2ª</span> – Em razão deste Termo Aditivo:</p>

    <ul class="changes">
        @foreach ($document['items'] as $item)
            <li>
                {{ $item['text'] }}
                @if ($item['children'])
                    <ul>
                        @foreach ($item['children'] as $child)
                            <li>{{ $child }}</li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>

    <p><span class="clause-number">2.1</span> – {{ $document['value_clause'] }}</p>

    @if ($document['payment_terms'])
        <p><span class="clause-number">2.2</span> – {{ $document['payment_terms'] }}</p>
    @endif

    <h3>DAS DISPOSIÇÕES GERAIS</h3>

    <p>
        <span class="clause-number">Cláusula 3ª</span> – Na hipótese de cancelamento do Contrato por parte da
        <strong>CONTRATANTE</strong>, haverá incidência de multa rescisória, sem prejuízo das perdas e danos calculados,
        de acordo com as alterações ora pactuadas.
    </p>

    <p>
        <span class="clause-number">Cláusula 4ª</span> – O presente contrato tem efeito de título executivo
        extrajudicial, na forma do art. 784, III, do Código de Processo Civil.
    </p>

    <p>
        <span class="clause-number">Cláusula 5ª</span> – Este Termo Aditivo só terá validade mediante assinatura e
        devolução à <strong>CONTRATADA</strong>, no prazo de 24 horas. Caso contrário, serão mantidas as condições do
        Contrato original.
    </p>

    <p>
        <span class="clause-number">Cláusula 6ª</span> – Este Termo Aditivo restará integrado ao contrato original
        firmado entre as partes, acerca do qual permanecem em pleno vigor todas as cláusulas e condições ajustadas, para
        todos os efeitos de direito, observadas as modificações ora ajustadas.
    </p>

    <p>
        E por estarem assim justos e contratados, assinam este instrumento em três vias de igual teor e forma, para que
        produza os efeitos legais, na presença de duas testemunhas a tudo presenciaram.
    </p>

    <p class="issued-at">
        Viçosa, {{ \Carbon\Carbon::parse($document['issued_at'])->translatedFormat('d \d\e F \d\e Y') }}
    </p>

    <div class="signature">
        @if ($contractant['is_company'])
            @foreach ($representatives as $representative)
                <div class="party">
                    <p>_________________________________________</p>
                    <p><strong>CONTRATANTE:</strong></p>
                    <p>{{ $contractant['name'] }}</p>
                    <p>CNPJ: {{ $contractant['document'] }}</p>
                    <p>Representada por: {{ $representative['name'] }}</p>
                    <p>CPF: {{ $representative['cpf'] }}</p>
                </div>
            @endforeach
        @else
            <div class="party">
                <p>_________________________________________</p>
                <p><strong>CONTRATANTE:</strong></p>
                <p>{{ $contractant['name'] }}</p>
                <p>CPF: {{ $contractant['document'] }}</p>
                @if ($contractant['rg'])
                    <p>RG: {{ $contractant['rg'] }}</p>
                @endif
            </div>
        @endif

        <div class="party">
            <p>_________________________________________</p>
            <p><strong>CONTRATADO:</strong></p>
            <p>PINHEIRO E BALTAZAR LTDA</p>
            <p>CNPJ 26.282.057/0001-14</p>
        </div>
    </div>
</body>

</html>
