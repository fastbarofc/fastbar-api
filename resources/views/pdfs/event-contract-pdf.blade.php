<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Prestação de Serviços de Coquetelaria</title>

    <style>
        body {
            margin-left: 50px;
            margin-right: 50px;
            padding-top: 50px;
            color: #333;
            font-family: 'Times New Roman', serif;
            font-size: 12px;
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

        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .clause-number {
            font-weight: bold;
        }

        .event-summary,
        .bank-account {
            margin-left: 20px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 20px 0;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .menu-list {
            margin-top: 0;
        }

        .signature {
            text-align: center;
            margin-top: 50px;
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

    <div class="section-title">DO OBJETO DA CONTRATAÇÃO</div>

    <p>
        <span class="clause-number">Cláusula 1ª</span> – O presente CONTRATO tem por objeto a prestação dos serviços de
        coquetelaria a CONTRATANTE especificamente no evento que se realizará, conforme disposto abaixo:
    </p>

    <div class="event-summary">
        <p>
            Evento: {{ $contract['event']['name'] }}<br>
            Local: {{ $contract['event']['place'] }}<br>
            Data: {{ $contract['event']['date'] }}<br>
            Horário de Início: {{ $contract['event']['time'] }}<br>
            Duração: {{ $contract['event']['duration'] }} horas<br>
            Número de convidados: {{ $contract['event']['guests'] }}
        </p>
    </div>

    <div class="section-title">DO PREÇO E CONDIÇÕES DE PAGAMENTO</div>

    <p>
        <span class="clause-number">Cláusula 2ª</span> – Pela prestação dos serviços objetivados no presente contrato,
        constantes da cláusula primeira, a CONTRATANTE se compromete a pagar ao CONTRATADO o valor de R$
        {{ number_format($contract['price']['unit'], 2, ',', '.') }} por pessoa, totalizando valor de R$
        {{ number_format($contract['price']['total'], 2, ',', '.') }}.
    </p>
    <p>
        2.1- No dia do evento, excedendo a tolerância de duração do evento, será acrescentado a quantia de 10% por hora
        excedida pela parte contratante, a ser acertado em até 03 (três) dias úteis após a data do evento.
    </p>
    <p>
        2.2- No dia do evento excedendo o número de convidados contratados, será cobrado o valor R$
        {{ number_format($contract['price']['excess_unit'], 2, ',', '.') }} por convidado excedido, a ser acertado em
        até 03 (três) dias úteis após a data do evento.
    </p>

    <p>
        <span class="clause-number">Cláusula 3ª</span> – Os pagamentos serão efetuados em sua totalidade em até 30 dias
        antes da realização do evento via {{ $contract['payment']['method'] }} da seguinte forma:
    </p>

    <div class="event-summary">
        @forelse ($contract['payment']['installments'] as $installment)
            <p>
                {{ $installment['label'] }}{{ $installment['share'] ? ' – ' . $installment['share'] : '' }}{{ $installment['due_date'] ? ' até ' . $installment['due_date'] : '' }}
                (R$ {{ number_format($installment['amount'], 2, ',', '.') }})
            </p>
        @empty
            <p>Pagamento à vista no valor de R$ {{ number_format($contract['price']['total'], 2, ',', '.') }}.</p>
        @endforelse
    </div>

    <p><strong>Conta para depósito:</strong></p>
    <div class="bank-account">
        <p>
            Banco: Bradesco<br>
            Agência: 986<br>
            Conta Corrente: 9205-3<br>
            Favorecido: Pinheiro e Baltazar<br>
            Chave Pix (CNPJ): 26282.057/0001-14
        </p>
    </div>

    <p>
        <span class="clause-number">Cláusula 4ª</span> – O preço ora pactuado abrange todos os encargos decorrentes dos
        serviços prestados e de responsabilidade do CONTRATADO, tais como encargos sociais, trabalhistas e
        previdenciários incidentes ou que venham a incidir sobre este contrato ou sobre a prestação de serviço dele
        objeto; além dos encargos fiscais e tributários e quaisquer outras despesas, diretas ou indiretas, necessárias
        para que o CONTRATADO possa dar exato cumprimento ao presente contrato.
    </p>

    <p>
        <span class="clause-number">Cláusula 5ª</span> – Os valores do presente contrato serão atualizados
        monetariamente pelo índice do IGPM (FGV) acumulados mensalmente a partir da data de assinatura do presente
        contrato de Prestação de Serviços, caso o índice ultrapasse 12% ao ano, até as datas dos respectivos pagamentos,
        sempre sobre o saldo devedor. Na falta do IGPM (FGV), este índice será substituído por outro que venha a
        substituí-lo determinado pela política econômica do governo.
    </p>

    <div class="section-title">DO INADIMPLEMENTO</div>

    <p>
        <span class="clause-number">Cláusula 6ª</span> – Em caso de PAGAMENTO PARCELADO: O atraso no pagamento de alguma
        das parcelas pelo período em que durar a inadimplência, incidirá multa de 2% (dois por cento), juros moratórios
        de 1% (um por cento) ao mês e correção monetária plena, calculada proporcionalmente, pela variação do IGPM-FGV
        ou outro índice que o substitua em caso de extinção.
    </p>

    <p>
        <span class="clause-number">Cláusula 7ª</span> – Em caso de PAGAMENTO PARCELADO ou PAGAMENTO MEDIANTE CHEQUE:
        Persistindo a inadimplência por prazo superior a 10 (dez) dias, a CONTRATANTE será notificada por escrito para
        efetuar o pagamento da fatura em atraso, no prazo de 48 (quarenta e oito) horas após a notificação. Caso não
        seja realizado o pagamento no prazo determinado, o CONTRATADO poderá prontamente, suspender a futura execução da
        prestação do serviço.
    </p>

    <p>
        <span class="clause-number">Cláusula 8ª</span> – A qualquer tempo enquanto ocorrer o inadimplemento dos
        pagamentos compactuados, a CONTRATANTE, ficará sujeita às medidas judiciais de execução, acrescidas, neste caso,
        de todas as despesas processuais, mais honorários advocatícios no importe de 20% (vinte por cento) sobre o valor
        da causa, sem prejuízo do disposto nas cláusulas 6ª (sexta) e 7ª (sétima).
    </p>

    <div class="section-title">DOS SERVIÇOS E PROFISSIONAIS</div>

    <p>
        <span class="clause-number">Cláusula 9ª</span> – Os serviços contratados serão executados de acordo com as
        instruções contidas neste contrato, bem como posteriores recomendações específicas que venham a ser feitas pela
        CONTRATANTE, com a devida aprovação do CONTRATADO.
    </p>
    <p>
        9.1- Quando a cerimônia do religioso ocorrer em local diferente do salão da recepção, o início da contagem do
        tempo de evento, será dado após: o encerramento da cerimônia, comunicada pela equipe de Cerimonial e a partir da
        chegada do primeiro convidado.
    </p>
    <p>
        9.2- Quando a cerimônia do religioso ocorrer no mesmo local da recepção, o início da contagem do tempo de evento
        se dará imediatamente após o encerramento da cerimônia religiosa.
    </p>

    <p>
        <span class="clause-number">Cláusula 10ª</span> – A prestação dos serviços de coquetelaria, ora contratados,
        terão início a partir do horário firmado em contrato.
    </p>
    <p>
        <strong>Parágrafo único</strong> – O horário previsto para o início do evento será observado pela CONTRATANTE e
        pelo CONTRATADO, sob pena de multa no valor correspondente a 1 (um) salário mínimo, por cada hora de atraso,
        observada a tolerância de 30 (trinta) minutos.
    </p>

    <p>
        <span class="clause-number">Cláusula 11ª</span> – Em nenhuma hipótese haverá a prestação dos serviços de
        coquetelaria em horário anterior ao contratado.
    </p>

    <p>
        <span class="clause-number">Cláusula 12ª</span> – O mobiliário oferecido pelo CONTRATADO conforme descrito na
        cláusula 23ª (vigésima terceira), será oferecido de acordo com a disponibilidade de estoque do CONTRATADO, sendo
        vedado a CONTRATANTE realizar escolha sobre referidos materiais.
    </p>

    <p>
        <span class="clause-number">Cláusula 13ª</span> – A CONTRATANTE poderá reduzir o número de convidados, com até
        30 (trinta) dias de antecedência, com a consequente modificação no preço pactuado, conforme tabela abaixo:
    </p>

    <table class="summary-table">
        <tr>
            <th>PATAMAR DE REDUÇÃO DE CONVIDADOS COMPACTUADO INICIALMENTE</th>
            <th>ACRÉSCIMO NO VALOR DO CONTRATO</th>
        </tr>
        <tr>
            <td>ATÉ 5%</td>
            <td>NÃO HAVERÁ ALTERAÇÃO NO VALOR</td>
        </tr>
        <tr>
            <td>DE 6 A 10%</td>
            <td>5%</td>
        </tr>
        <tr>
            <td>DE 11 A 20%</td>
            <td>8%</td>
        </tr>
    </table>

    <p>
        13.1- A formalização da alteração para redução no número de convidados será objeto de termo aditivo contratual,
        a fim de produzir seus jurídicos e legais efeitos.
    </p>
    <p>
        13.2- Na hipótese de remanescer crédito em favor da CONTRATANTE, em razão da redução do número de convidados e
        consequente atualização do valor contratado, o montante será deduzido da última parcela a ser paga. Caso o
        pagamento tenha sido à vista, o CONTRATADO efetuará a restituição no prazo de até 3 (três) dias úteis após a
        realização do evento.
    </p>
    <p>
        13.3- A redução do número de convidados em patamar superior a 20% (vinte por cento) acarreta o desequilíbrio
        econômico do contrato, o que em hipótese alguma será permitido.
    </p>

    <p>
        <span class="clause-number">Cláusula 14ª</span> – O aumento do número de convidados não implicará na alteração
        do valor cobrado por pessoa, e causará alteração apenas no valor total do contrato, que será formalizado
        mediante assinatura de termo aditivo contratual, a fim de produzir seus jurídicos e legais efeitos.
    </p>
    <p>
        14.1 – O aumento no número de convidados será sempre limitado à capacidade do espaço locado bem como a
        comunicação prévia de 20 (vinte) dias corridos.
    </p>

    <p>
        <span class="clause-number">Cláusula 15ª</span> – A CONTRATANTE fica isenta de pagamento de convidados com a
        idade mínima de isenção aceita e negociada com o Buffet contratado.
    </p>

    <p>
        <span class="clause-number">Cláusula 16ª</span> – O comparecimento dos convidados em número menor do
        estabelecido no contrato e/ou aditivos, não dará direito de ressarcimento, de qualquer espécie, a CONTRATANTE.
    </p>

    <p>
        <span class="clause-number">Cláusula 17ª</span> – A CONTRATANTE poderá servir bebidas não contratadas no
        presente instrumento, mediante a comunicação ao CONTRATADO em até 30 (trinta) dias antes da data do evento, e
        tal ato será objeto de termo aditivo contratual, para que possa produzir seus efeitos jurídicos e legais.
    </p>

    <p>
        <span class="clause-number">Cláusula 18ª</span> – A CONTRATANTE poderá realizar alterações no cardápio
        escolhido.
    </p>
    <p>
        <strong>Parágrafo Único</strong> – A CONTRATANTE deve comunicar ao CONTRATADO em até 60 (sessenta) dias antes da
        data do evento e esta alteração será objeto de termo aditivo contratual, para que possa produzir seus efeitos
        jurídicos e legais, ainda, nesta hipótese o valor do contrato poderá ser devidamente reajustado.
    </p>

    <p>
        <span class="clause-number">Cláusula 19ª</span> – A alteração da data do evento poderá ser remarcada caso
        CONTRATANTE ou CONTRATADO necessite, hipótese em que deverá ser solicitado em até 180 (cento e oitenta) dias
        antes da data do evento inicialmente marcada, para a prestação dos serviços ora avençados, de acordo com a
        disponibilidade de datas na programação de eventos do CONTRATADO, o que só será aceito mediante aditivo
        contratual.
    </p>

    <p>
        <span class="clause-number">Cláusula 20ª</span> – Será fornecido pelo CONTRATADO o material necessário para a
        prestação dos serviços ora contratados bem como transporte de material e pessoal.
    </p>

    <p>
        <span class="clause-number">Cláusula 21ª</span> – O cardápio oferecido será composto da forma que segue abaixo:
    </p>
    <p><strong>MODALIDADE ESCOLHIDA: {{ $contract['menu']['modality'] }}</strong></p>
    <ul class="menu-list">
        @foreach ($contract['menu']['items'] as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <p><span class="clause-number">Cláusula 22ª</span> – Será fornecido pelo CONTRATADO:</p>
    <p>22.1 - A equipe de bartenders, necessários para a prestação dos serviços ora contratados.</p>
    <p>22.2 – {{ $contract['bar_points_text'] }}</p>
    <p>
        22.3 – Copos de Vidro necessários para a realização de {{ $contract['cups']['glass_hours'] }} horas iniciais de
        evento e copos de acrílico para as {{ $contract['cups']['acrylic_hours'] }} horas finais de evento.
    </p>

    <p>
        <span class="clause-number">Cláusula 23ª</span> – A adição de 1 (um) ponto de bar do tipo “lateral”, será feita
        mediante acréscimo de 2,50 reais por pessoa. A troca do tipo de ponto de bar para o tipo 360º, terá custo
        adicional de 10% no valor do contrato.
    </p>

    <div class="section-title">OBRIGAÇÕES DO CONTRATADO</div>

    <p><span class="clause-number">Cláusula 24ª</span> – O CONTRATADO assume às seguintes obrigações:</p>
    <p>
        a) Oferecer com qualidade e dentro das normas de higiene e limpeza o cardápio escolhido pela CONTRATANTE, cujas
        especificações, encontram-se na cláusula 21ª (vigésima primeira), do presente instrumento.
    </p>
    <p>
        b) Fornecer a mão de obra necessária à prestação dos serviços objetivados no presente contrato, zelando para que
        os funcionários compareçam pontualmente no local do evento devidamente uniformizados e asseados, exercendo suas
        funções com educação, urbanidade e moralidade, afastando e substituindo aqueles cuja conduta não for adequada.
    </p>
    <p>
        c) Fornecer os utensílios e mobiliário necessário para a realização do evento, conforme descrito na cláusula 12ª
        (décima segunda), o qual será cedido, a título de empréstimo durante a realização do evento.
    </p>

    <div class="section-title">OBRIGAÇÕES DA CONTRATANTE</div>

    <p><span class="clause-number">Cláusula 25ª</span> – A CONTRATANTE assume às seguintes obrigações:</p>
    <p>a) Cumprir com as obrigações de pagamento, nos prazos e condições estipuladas no presente contrato.</p>
    <p>
        b) Fornecer ao CONTRATADO todas as informações necessárias à realização adequada do serviço, devendo especificar
        os detalhes do evento, necessários a sua perfeita realização.
    </p>
    <p>
        c) Responsabilizar-se pela conservação ao mobiliário fornecido pelo CONTRATADO para a realização do evento,
        assumindo, portanto, a obrigação de indenizar todas as perdas e danos, totais ou parciais, causados pelos
        convidados.
    </p>
    <p>
        d) Responder civil e penalmente por prejuízos de qualquer natureza, independente de dolo ou culpa por qualquer
        dano ao mobiliário fornecido pelo CONTRATADO para a realização do evento.
    </p>
    <p>
        e) Responsabilizar-se pela contratação e execução dos serviços de segurança, banda, DJ, som, iluminação,
        ornamentação e decoração do local do evento, observando rigorosamente as normas de segurança, incluindo a
        alimentação dos referidos prestadores de serviços.
    </p>
    <p>f) Responsabilizar-se pelo consumo de bebida alcoólica por convidado menor de idade durante o evento.</p>
    <p>
        g) Responsabilizar-se ampla e irrestritamente pelos seus convidados, os quais serão recepcionados no evento
        mediante a apresentação de convite oficial fornecido pelo CONTRATANTE, ficando a CONTRATADA isenta de quaisquer
        responsabilidades por eventuais fraudes e/ou falsificações de convites.
    </p>
    <p>
        h) Fornecer o local de acordo com a regulamentação estabelecida pelos órgãos competentes no que se refere às
        instalações e ambientes próprios para o manuseio e consumo de alimentos seguros (Resolução RDC Nº 216 de
        15/09/2004, Resolução RDC Nº 275 DE 21/10/2202, Portaria SVS/MS Nº 326 de 30/07/1997, Portaria CVS 5 de
        09/04/2013, dentre outras normas regulamentadoras do tema), isentando a CONTRATANTE de responsabilidade pelo
        risco de contaminação de alimentos advindo da infringência ou não observação das normas impostas.
    </p>

    <div class="section-title">DA RESCISÃO</div>

    <p>
        <span class="clause-number">Cláusula 26ª</span> – O presente contrato poderá ser rescindido pela CONTRATANTE,
        independentemente de justificação ou interpelação judicial ou extrajudicial, caso o CONTRATADO infrinja qualquer
        cláusulas ou condições deste contrato.
    </p>

    <p>
        <span class="clause-number">Cláusula 27ª</span> – O prazo máximo para a CONTRATANTE solicitar o cancelamento dos
        serviços contratados é de até 90 (noventa) dias antes da data do evento prevista na cláusula 1ª (primeira).
    </p>

    <p>
        <span class="clause-number">Cláusula 28ª</span> – Na hipótese de cancelamento do contrato, por parte da
        CONTRATANTE, haverá incidência de multa rescisória, sem prejuízo das perdas e danos, sendo esta multa calculada
        progressivamente sobre o valor total do contrato, ora firmado entre as partes, conforme disposto abaixo:
    </p>

    <table class="summary-table">
        <tr>
            <th>DIAS FALTANTES PARA A EXECUÇÃO DA PRESTAÇÃO DE SERVIÇOS CONTRATADOS</th>
            <th>MULTA SOBRE O VALOR TOTAL DO CONTRATO</th>
        </tr>
        <tr>
            <td>ATÉ 365 DIAS</td>
            <td>20%</td>
        </tr>
        <tr>
            <td>DE 364 DIAS ATÉ 120 DIAS</td>
            <td>30%</td>
        </tr>
        <tr>
            <td>DE 119 DIAS ATÉ 90 DIAS</td>
            <td>40% E EVENTUAIS PERDAS E DANOS</td>
        </tr>
    </table>

    <p>
        <strong>Parágrafo único</strong> – A CONTRATADA promoverá a restituição do valor efetivamente pago no prazo de
        10 (dez) dias úteis após a assinatura do termo de distrato, deduzido os valores correspondentes à multa e
        eventuais perdas e danos, caso a CONTRATANTE decida pelo cancelamento do contrato.
    </p>

    <p>
        <span class="clause-number">Cláusula 29ª</span> – A CONTRATANTE não poderá rescindir o presente contrato em data
        próxima à do evento, a saber, faltando menos de 90 (noventa) dias para a data do evento inicialmente contratado,
        uma vez que a fase de planejamento da prestação de serviços contratados, como por exemplo, compras, contratação
        de pessoal e produção, se inicia 90 (noventa) dias antes da data estipulada para o evento, onde ocorrerá a
        prestação dos serviços aqui contratados.
    </p>

    <p>
        <span class="clause-number">Cláusula 30ª</span> – O presente contrato somente será extinto e produzirá efeitos
        mediante assinatura do termo de distrato.
    </p>

    <div class="section-title">DA FORÇA MAIOR OU CASO FORTUITO</div>

    <p>
        <span class="clause-number">Cláusula 31ª</span> – A CONTRATADA não será responsável perante a CONTRATANTE por
        qualquer falha ou atraso no cumprimento das obrigações constantes no presente contrato, causados por casos
        fortuitos ou força maior nos termos do artigo 393 do Código Civil.
    </p>

    <p>
        <span class="clause-number">Cláusula 32ª</span> – Na hipótese de adiamento ou cancelamento dos serviços
        objetivados neste Contrato por ocasião da COVID, serão aplicadas as normas estabelecidas na Lei 14.046/20, com
        as alterações incluídas pela Lei 14.186/2.
    </p>

    <p>
        <span class="clause-number">Cláusula 33ª</span> – A CONTRATADA afetada por qualquer evento de força maior ou
        caso fortuito comunicará o fato à CONTRATANTE imediatamente e, no menor prazo possível, esclarecendo as
        circunstâncias, as ações em curso para amenizar as perdas e solucionar o ocorrido, o tempo estimado de duração e
        tudo o mais que for necessário à compreensão do fato, suas consequências e solução.
    </p>

    <div class="section-title">DISPOSIÇÕES GERAIS</div>

    <p>
        <span class="clause-number">Cláusula 34ª</span> – No âmbito deste contrato, as partes sempre se comunicarão por
        escrito.
    </p>

    <p>
        <span class="clause-number">Cláusula 35ª</span> – Os contratantes reconhecem que a adoção dos valores pactuados
        é condição essencial do negócio, necessária ao seu equilíbrio econômico.
    </p>

    <p>
        <span class="clause-number">Cláusula 36ª</span> – Qualquer alteração, modificação, complementação, ou ajuste,
        somente será reconhecido e produzirá efeitos legais, se incorporado ao presente contrato mediante Termo Aditivo,
        devidamente reconhecido e assinado pelas partes contratantes.
    </p>

    <p>
        <span class="clause-number">Cláusula 37ª</span> – Este contrato não criará qualquer vínculo empregatício entre
        os empregados/prepostos do CONTRATADO e a CONTRATANTE, obrigando-se o primeiro a, se for o caso, apresentar-se
        em Juízo e a declarar a isenção de responsabilidade da CONTRATANTE.
    </p>

    <p>
        <span class="clause-number">Cláusula 38ª</span> – O presente contrato tem efeito de título executivo
        extrajudicial, na forma do art. 784, III, do Código de Processo Civil.
    </p>

    <div class="section-title">DAS INFRAÇÕES</div>

    <p>
        <span class="clause-number">Cláusula 39ª</span> – Ocorrendo qualquer violação às cláusulas e condições
        estabelecidas neste contrato, fica autorizado à rescisão contratual, cumulada com as multas previstas na
        cláusula 26ª (vigésima quinta).
    </p>

    <div class="section-title">DO FORO DE ELEIÇÃO</div>

    <p>
        <span class="clause-number">Cláusula 40ª</span> – As partes contratantes elegem o foro da Comarca de Viçosa –
        MG, para o fim de dirimir qualquer ação judicial oriunda do presente contrato.
    </p>

    <p>
        E, para firmeza e prova deste ajuste subscreve este instrumento particular em duas vias de igual teor e forma.
    </p>

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

    <table style="width: 100%; margin-top: 65px;">
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
