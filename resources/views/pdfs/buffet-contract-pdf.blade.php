<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Prestação de Serviços de Bufê</title>

    <style>
        body {
            margin-left: 50px;
            margin-right: 50px;
            padding-top: 50px;
            color: #333;
            font-family: 'Times New Roman', serif;
            font-size: 12px;
        }

        .section {
            margin-bottom: 20px;
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

        ol {
            list-style-type: disc;
            padding-left: 20px;
        }

        ol li {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
        }

        ol li::marker {
            color: #AA8534;
            font-size: 10px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000; /* Bordas para todas as células */
            padding: 5px;
            text-align: left;
        }

        .summary-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .summary-table td:nth-child(1) {
            width: 5%;
            text-align: center;
        }

        .summary-table td:nth-child(2) {
            width: 15%;
        }

        .summary-table td:nth-child(3) {
            width: 80%;
        }

        .clause {
            margin-bottom: 20px;
        }

        .clause-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            text-decoration: underline;
        }

        .clause-content {
            margin-left: 20px;
        }

        .clause-number {
    text-decoration: underline;
    font-weight: bold; 
        }

    </style>
</head>

<body>
    <div style="display: -webkit-box; -webkit-box-align: center; -webkit-box-pack: justify;">
        <p>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        <p class="bg-gold">ID: {{ $event->id }}</p>
    </div>

    <div class="logo-title">
        <h2 style="font-weight: normal;">CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE BUFFET</h2>
        <hr class="hr-strong">
    </div>

    <table class="summary-table">
        <tr>
            <th colspan="3" style="border: 1px solid #000;">Quadro-Resumo</th>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">01</td>
            <td style="border: 1px solid #000;">Contratada</td>
            <td style="border: 1px solid #000;">ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA., cujo nome fantasia é Buffet Parthenon, pessoa jurídica de direito privado, inscrita no CNPJ sob o n. 01.005.782/0001-14, com sede na Rua Antônio Lopes Lélis, n. 199, Bairro Santo Antônio, CEP 36.570-000, neste ato representada por seu sócio administrador, André Baratella Baltazar, inscrito no CPF sob o n. 071.448.506-36.</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">02</td>
            <td style="border: 1px solid #000;">Contratante</td>
            <td style="border: 1px solid #000;">{{ $client->name ?? 'Não informado' }}, {{ $client->nationality ?? 'Não informado' }}, {{ $client->civil_status ?? 'Não informado' }}, {{ $client->profession ?? 'Não informado' }}, inscrito(a) no CPF sob o n. {{ $client->cpf ?? 'Não informado' }} e portador(a) da CI de n. {{ $client->identity_card ?? 'Não informado' }}, residente e domiciliado(a) na Rua {{ $client->address['street'] ?? 'Não informado' }}, nº {{ $client->address['number'] ?? 'Não informado' }}{{ $client->address['complement'] ? ', ' . $client->address['complement'] : '' }}, Bairro {{ $client->address['district'] ?? 'Não informado' }}, CEP {{ $client->address['zip_code'] ?? 'Não informado' }}.</td>
        </tr>

        <tr>
            <td style="border: 1px solid #000;">03</td>
            <td style="border: 1px solid #000;">Objeto</td>
            <td style="border: 1px solid #000;"></td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">04</td>
            <td style="border: 1px solid #000;">Evento</td>
            <td style="border: 1px solid #000;">
                Nome: {{ $event->name }}<br>
                Local: {{ $place?->name }}<br>
                Data: {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}<br>
                Horário: {{ $event->time ? \Carbon\Carbon::parse($event->time)->format('H:i') : 'Hora a definir' }}<br>
                Duração: {{ $event->duration }} horas<br>
                Número de Convidados: {{ $event->event_guests }}
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">05</td>
            <td style="border: 1px solid #000;">Preço</td>
            <td style="border: 1px solid #000;"></td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">06</td>
            <td style="border: 1px solid #000;">Forma de Pagamento</td>
            <td style="border: 1px solid #000;"></td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">07</td>
            <td style="border: 1px solid #000;">Data de Assinatura</td>
            <td style="border: 1px solid #000;">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <p style="text-indent: 10em;">• <strong>ANEXO I:</strong> Cardápio.</p>

    <div class="clause">
        <div class="clause-title">DO OBJETO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 1ª.</span> O presente Contrato tem por objeto a prestação, pela  <strong>Contratada</strong> à <strong>Contratante</strong>, de serviços de bufê, cuja execução dar-se-á no evento devidamente especificado no Quadro-Resumo (nome, local, data, horário, duração e número de convidados) e conforme os termos que se consagram nas regras a seguir estipuladas.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO PREÇO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 2ª.</span> Pela prestação dos serviços objeto da presente avença, a  <strong>Contratante</strong> pagará à <strong>Contratada</strong> o preço ajustado, do mesmo modo como na Cláusula anterior, no Quadro-Resumo, no qual restará estabelecida, também, a forma de pagamento.</p>
            <p><strong>§1º.</strong> O preço acordado entre as partes para a execução, pela  <strong>Contratada</strong>, da presente avença abrange todos os encargos decorrentes dos serviços prestados que sejam de responsabilidade da ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA., tais como encargos sociais, trabalhistas e previdenciários. Do mesmo modo, estão inclusos, no preço, os incidentes que, porventura, venham a recair sobre este Contrato ou sobre a prestação de serviços, além dos encargos fiscais e tributários e quaisquer outras despesas, diretas ou indiretas, necessárias ao seu exato cumprimento.</p>
            <p><strong>§2º.</strong> Se se exceder, no dia do evento, a duração previamente acordada entre as partes, a  <strong>Contratante</strong> pagará, <strong>por hora excedida</strong>, à <strong>Contratada</strong> quantia equivalente a 10% (dez por cento) sobre o valor total deste Contrato. Na oportunidade, as partes celebrarão "TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS", no qual constarão:</p>
            <ol>
                <li>As especificações de horário e forma de pagamento do valor excedente; e</li>
                <li>A forma de pagamento: sempre via Boleto Bancário e em até 10 (dez) dias úteis após a data do evento.</li>
            </ol>
            <p><strong>§3º.</strong> Em caso de aumento do número de convidados ou quaisquer alterações de Cardápio, observado, em todos os casos, o prazo posto na Cláusula 12 desta avença, <strong>e por questão de equilíbrio contratual, haja vista a variação de preços dos produtos no mercado</strong>, a <strong>Contratante</strong> pagará o preço praticado pela <strong>Contratada</strong> <strong>na data da solicitação</strong> -- seja de aumento de convidados, seja de alteração do Cardápio --, concordando, desde já, com o eventual aumento nos preços dos produtos e serviços.</p>
            <p><strong>§4º.</strong> A <strong>Contratante</strong> está ciente de que a ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA. realiza, em todos os seus eventos, controle do número efetivo de convidados, fazendo-o por meio da contagem e conferência de convites e/ou lista de convidados entregue na Portaria. Essa contagem e conferência, deve-se destacar, poderá ser realizada em duas datas diversas, ou na própria data do evento, ou em data que lhe for posterior, haja vista que os convites entregues na data do evento são devidamente recolhidos e arquivados pela <strong>Contratada</strong>.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO INADIMPLEMENTO QUANTO AO PAGAMENTO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 3ª.</span> Em caso de inadimplemento do preço na data acertada, tenha se convencionado o pagamento à vista, parcelado ou por meio de cheque, aplicar-se-á, sobre o débito, multa de 2% (dois por cento), mais juros de mora de 1% (um por cento) ao mês, contados pro rata die, e correção monetária, utilizando-se, para tanto, índice do IGPM/FGV ou qualquer outro que venha a lhe substituir.</p>
            <p><strong>Parágrafo Único.</strong> Na hipótese de ser emitido cheque sem fundos nominal não à ordem à <strong>Contratada</strong>, restará configurado, por conseguinte, o inadimplemento da parcela, incidindo, sobre o débito, as penalidades descritas no caput desta Cláusula.</p>
            <p><span class="clause-number">Cláusula 4ª.</span> Persistindo a inadimplência por período superior a 10 (dez) dias, a Contratante será notificada, por escrito, para efetuar o pagamento dos valores em atraso, no prazo de até 48 (quarenta e oito) horas, sob pena de cancelamento do presente Contrato. <u>Ocorrendo efetivamente a rescisão por inadimplência da Contratante, não haverá devolução, pela Contratada, das quantias eventualmente já quitadas.</u></p>
            <p><span class="clause-number">Cláusula 5ª.</span> A qualquer tempo, enquanto restar configurado o inadimplemento dos valores devidos, a Contratante estará sujeita às medidas judiciais de execução, acrescidas, neste caso, de todas as despesas processuais, mais honorários advocatícios no importe de 20% (vinte por cento) sobre o valor da causa, sem prejuízo de aplicação, também, das penalidades estabelecidas na Cláusula 3ª.</p>
            <p><span class="clause-number">Cláusula 6ª.</span> O valor total descrito avençado deverá estar integralmente quitado até 30 (trinta) dias antes do evento, sob pena de cancelamento imediato deste Contrato, sem o direito de ressarcimento, pela Contratada, das quantias eventualmente pagas.</p> 
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO REAJUSTE</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 7ª.</span> O preço contratual será atualizado, anualmente, pelo índice do IGPM/FGV, iniciando-se a respectiva periodicidade após 12 (doze) meses contados da assinatura do presente Contrato. Na falta do IGPM/FGV, este índice será substituído por outro que venha a substituí-lo em determinação a política econômica do Governo.</p>
        </div>
    </div>

    <div class="clause">
    <div class="clause-title">DOS SERVIÇOS E PROFISSIONAIS</div>
    <div class="clause-content">
        <p><span class="clause-number">Cláusula 8ª.</span> Os serviços objeto da presente avença serão executados de acordo com as instruções contidas neste Contrato e com as posteriores recomendações específicas que, porventura, venham a ser feitas pela Contratante, desde que devidamente aprovadas pela Contratada.</p>
        <p style="text-align: center; font-weight: bold; text-decoration: underline;">1. Do Horário do Evento e da Tolerância.</p>
        <p><span class="clause-number">Cláusula 9ª.</span> Ressalvada uma tolerância de 30 (trinta) minutos, o horário previsto para o início do evento deverá ser respeitado pelas partes, sob pena de ser cobrada uma multa no valor correspondente a 1 (um) salário-mínimo por cada hora de atraso.</p>
        <p><span class="clause-number">Cláusula 10ª.</span> Em nenhuma hipótese haverá a prestação dos serviços de bufê em horário anterior ao contratado.</p>
        <p style="text-align: center; font-weight: bold; text-decoration: underline;">2. Do Mobiliário.</p>
        <p><span class="clause-number">Cláusula 11ª.</span> Se necessário MOBILIÁRIO para a prestação dos serviços de bufê ora contratados, nos termos da Cláusula 21, inciso III, as peças serão oferecidas de acordo com a disponibilidade de estoque da Contratada, sendo vedada à Contratante a escolha dos referidos materiais.</p>
        <p style="text-align: center; font-weight: bold; text-decoration: underline;">2. 3. Do Aumento e da Redução do Número de Convidados.</p>
        <p><span class="clause-number">Cláusula 12ª.</span> A Contratante poderá reduzir ou aumentar o número de convidados contratados em até 30 (TRINTA) DIAS ANTES DA REALIZAÇÃO DO EVENTO, quando as partes celebrarão “TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS”, ciente das modificações no preço ajustado, conforme as condições abaixo estipuladas:</p>
        <p style="text-align: center;"><strong>I -</strong> Para a DIMINUIÇÃO DO NÚMERO DE CONVIDADOS, seguir-se-á seguir os seguintes critérios:</p>

        <table class="summary-table" style="width: 80%; border-collapse: collapse;">
        <tr>
            <td style="border: 1px solid #000; width: 50%;"><strong>Patamar de Redução de Convidados Inicialmente Contratados</strong></td>
            <td style="border: 1px solid #000; width: 50%;"><strong>Acréscimo no Valor por Convidado</strong></td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">Até 5%</td>
            <td style="border: 1px solid #000;">Até 8%</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 6 a 10%</td>
            <td style="border: 1px solid #000;">12%</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 11 a 15%</td>
            <td style="border: 1px solid #000;">15%</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 16 a 19%</td>
            <td style="border: 1px solid #000;">18%</td>
        </tr>
        </table>

        <p style="text-align: center;"><strong>II -</strong> Para o AUMENTO, respeitando-se, sempre, o limite de capacidade do espaço do local onde será realizado o evento, por sua vez:</p>

        <table class="summary-table" style="width: 80%; border-collapse: collapse;">
        <tr>
            <td style="border: 1px solid #000; width: 50%;"><strong>Patamar de Aumento de Convidados Inicialmente Contratados</strong></td>
            <td style="border: 1px solid #000; width: 50%;"><strong>Limite de Prazo</strong></td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">Até 10%</td>
            <td style="border: 1px solid #000;">Até 05 dias antes do evento</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 10 a 20%</td>
            <td style="border: 1px solid #000;">Até 10 dias antes do evento</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">Acima de 20%</td>
            <td style="border: 1px solid #000;">Até 20 dias antes do evento</td>
        </tr>
        </table>

        <p><strong>§1º.</strong> Não será permitida a redução do número de convidados em patamar superior a 20% (vinte por cento) do contratado, em decorrência do desequilíbrio econômico acarretado.</p>
        <p><strong>§2º.</strong> Na hipótese de remanescer crédito em favor da <strong>Contratante</strong>, por ocasião da redução do número de convidados e consequente atualização do valor contratado, o montante será deduzido da última parcela a ser paga. Caso o pagamento tenha sido à vista, a <strong>Contratada</strong> efetuará a restituição no prazo de até 10 (dez) dias úteis após a realização do evento.</p>
        <p><strong>§3º.</strong> Na hipótese de o evento ser realizado fora das dependências da Contratada, a responsabilidade pela análise da quantidade/capacidade de convidados a que se refere o inciso II, caput, desta Cláusula, ficará a cargo da Contratante, eximindo-se a ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA. por quaisquer consequências decorrentes da inobservância do limite.</p>
        <p><strong><u>Cláusula 13.</u></strong> A <strong>Contratada</strong> se reserva no direito de levar ao evento até 2% (dois por cento) do número de convidados contratado, a fim de promover demonstração dos serviços prestados. O exercício deste direito não representa alteração contratual e não acarretará acréscimo de nenhuma natureza à <strong>Contratante</strong>, estando as despesas oriundas com a bebida e alimentação dos referidos convidados sob a integral responsabilidade da ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA.</p>
        <p><strong><u>Cláusula 14.</u></strong> O comparecimento de convidados em número inferior ao contratado, salvo as hipóteses de formalização de Termo Aditivo entre as partes acordando-se algo diverso, não dará direito de ressarcimento à <strong>Contratante</strong>, de qualquer espécie.</p>
        <p><strong><u>Cláusula 15.</u></strong> Os convites colhidos na recepção ficarão disponíveis na sede da <strong>Contratada</strong> pelo período de 15 (quinze) dias úteis após a realização do evento, sendo facultado à <strong>Contratante</strong> realizar a respectiva contagem e conferência. Após o referido prazo, os convites serão descartados.</p>
        <p><strong><u>• Da Isenção em relação aos Convidados Menores de 09 (nove) Anos.</u></strong></p>
        <p><strong><u>Cláusula 16.</u></strong> A <strong>Contratante</strong> fica isenta do pagamento correspondente aos convidados com idade de até 9 (nove) anos, mediante a apresentação de convite especial no ato da entrada, com destaque de tal condição.</p>
        <p><strong><u>• Da Alteração do Cardápio.</u></strong></p>
        <p><strong><u>Cláusula 17.</u></strong> Mediante a formalização de “TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS”, com antecedência mínima de 60 (sessenta) dias do evento, a <strong>Contratante</strong> poderá realizar alterações e acréscimos no cardápio escolhido, ciente de que o valor do contrato poderá ser reajustado de acordo com as novas escolhas.</p>
        <p><strong><u>• Da Alteração da Data do Evento.</u></strong></p>
        <p><strong><u>Cláusula 18.</u></strong> Também mediante a formalização de “TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS”, com antecedência mínima de 180 (cento e oitenta) dias do evento, a data inicialmente marcada para a prestação dos serviços ora contratados poderá ser alterada, caso quaisquer das partes necessite, de acordo com a disponibilidade de agenda da <strong>Contratada</strong>. Se a modificação for requerida pela <strong>Contratante</strong>, esta submeter-se-á, à época, aos valores então praticados pela ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA, comprometendo-se a quitar, se for o caso, a diferença, cuja forma de pagamento restará tratada no mesmo Aditivo.</p>
        <p><strong><u>• Dos Materiais e da Mão-de-Obra.</u></strong></p>
        <p><strong><u>Cláusula 19.</u></strong> Todos os materiais necessários para a prestação dos serviços objeto desta avença serão fornecidos pela <strong>Contratada</strong>, especificamente: taças para todos os fins, jarras, réchauds para todos os fins, bandejas para todos os fins, baldes de gelo, pratos, talheres, guardanapos, fritadeiras, fogões e fornos industriais, além do transporte de material e pessoal.</p>
        <p><strong><u>Cláusula 20.</u></strong> A <strong>Contratada</strong> fornecerá toda a equipe de pessoal, a saber: garçons, maitres, copeiros, auxiliares de cozinha e chefes de cozinha, de modo necessário para a prestação dos serviços ora contratados.</p>
     </div>
</div>

<div class="clause">
        <div class="clause-title">DAS OBRIGAÇÕES DA CONTRATADA</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 21.</u></strong> A <strong>Contratada</strong> assume as seguintes obrigações:</p>
        <p><strong>I -</strong> Oferecer com qualidade e dentro das normas de higiene e limpeza o Cardápio escolhido pela <strong>Contratante</strong>, cujas especificações encontram-se no <strong><u>ANEXO I</u></strong> do presente instrumento.</p>
        <p><strong>II -</strong> Fornecer a mão de obra necessária à prestação dos serviços objetivados no presente contrato, zelando para que os funcionários compareçam pontualmente no local do evento devidamente uniformizados e asseados, exercendo suas funções com educação, urbanidade e moralidade, afastando e substituindo aqueles cuja conduta não for adequada.</p>
        <p><strong>III -</strong> Fornecer os utensílios necessários para a realização do evento, conforme descrito na Cláusula 11, o qual será cedido, a título de empréstimo durante a realização do evento.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DAS OBRIGAÇÕES DA CONTRATANTE</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 21.</u></strong> A <strong>Contratada</strong> assume as seguintes obrigações:</p>
        <p><strong>I -</strong> Oferecer com qualidade e dentro das normas de higiene e limpeza o Cardápio escolhido pela <strong>Contratante</strong>, cujas especificações encontram-se no <strong><u>ANEXO I</u></strong> do presente instrumento.</p>
        <p><strong>II -</strong> Fornecer a mão de obra necessária à prestação dos serviços objetivados no presente contrato, zelando para que os funcionários compareçam pontualmente no local do evento devidamente uniformizados e asseados, exercendo suas funções com educação, urbanidade e moralidade, afastando e substituindo aqueles cuja conduta não for adequada.</p>
        <p><strong>III -</strong> Fornecer os utensílios necessários para a realização do evento, conforme descrito na Cláusula 11, o qual será cedido, a título de empréstimo durante a realização do evento.</p>
        <p><strong><u>DAS OBRIGAÇÕES DA CONTRATANTE</u></strong></p>
        <p><strong><u>Cláusula 22.</u></strong> A <strong>Contratante</strong> assume as seguintes obrigações:</p>
        <p><strong>I -</strong> Cumprir com as obrigações de pagamento, nos prazos e condições estipuladas no presente Contrato, especificamente no Quadro-Resumo.</p>
        <p><strong>II -</strong> Fornecer à <strong>Contratada</strong> todas as informações necessárias à exata e adequada prestação dos serviços objeto da presente avença, devendo especificar os detalhes do evento, necessários à sua perfeita realização.</p>
        <p><strong>III -</strong> Responsabilizar-se pela conservação do mobiliário contratado para a realização do evento, conforme o disposto na Cláusula 11, assumindo, portanto, a obrigação de indenizar todas as perdas e danos, totais ou parciais, causados pelos convidados ao serviço de bufê.</p>
        <p><strong>IV -</strong> Responsabilizar-se cível e penalmente pelos prejuízos causados, de qualquer natureza, independente de dolo ou culpa.</p>
        <p><strong>V -</strong> Responsabilizar-se pela contratação e execução dos serviços de segurança, banda, DJ, som, iluminação, ornamentação e decoração do local do evento, observando rigorosamente as normas de segurança.</p>
        <p><strong>VI -</strong> Responsabilizar-se pela alimentação dos prestadores de serviços eventualmente contratados conforme o disposto no inciso anterior.</p>
        <p><strong>VII -</strong> Responsabilizar-se pelo consumo de bebida alcoólica por convidado menor de idade durante o evento.</p>
        <p><strong>VIII -</strong> Responsabilizar-se, ampla e irrestritamente, pelos seus convidados, os quais serão recepcionados no evento mediante a apresentação de convite oficial fornecido pela <strong>Contratante</strong>, estando a <strong>Contratada</strong> isenta de quaisquer responsabilidades por eventuais fraudes e/ou falsificações de convites.</p>
        <p><strong>IX -</strong> Responsabilizar-se pela integridade física e moral dos seus convidados.</p>
        <p><strong>X -</strong> Assegurar o cumprimento das normas de segurança/higiene, conforme instruções emanadas pelos órgãos de saúde pública, especificamente no que tange ao distanciamento social para controle de situações pandêmicas.</p>
        <p><strong>XI -</strong> A ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA. responsabilizar-se-á tão somente pelo acondicionamento do lixo produzido, fazendo-o devidamente em sacos plásticos, sendo, porém, de obrigação exclusivamente da <strong>Contratante</strong> oferecer local adequado, com capacidade suficiente e de fácil acesso para o seu descarte.</p>
        <p><strong>XII -</strong> Assegurar que a parte elétrica do local do evento estará em pleno e regular funcionamento com, pelo menos, 06 (seis) horas de antecedência do horário acordado para início do evento, disponibilizando, ainda, às suas expensas, profissional qualificado para solucionar quaisquer imprevistos que possam ocorrer e prejudicar a boa execução da prestação de serviços de buffet contratados, o qual deverá estar disponível durante todo o período do evento.</p>
        <p><strong>XIII -</strong> Assegurar, frisa-se, que o profissional referido na alínea anterior, que, destaque-se, deverá estar à disposição durante todo o período do evento, esteja munido de todo o material necessário à correta, estável e segura condução de energia elétrica até os pontos em que a mesma se faça imprescindível – o quais serão listados pela <strong>Contratada</strong> anteriormente à data marcada para a realização do evento –, como, por exemplo, mas não se limitando, local em que estão os freezers, pontos de chopp etc.</p>
        <p><strong>§1º.</strong> É imprescindível a observância, pela <strong>Contratante</strong>, do que posto no caput desta Cláusula, assegurando-se, à <strong>Contratada</strong>, local adequado para o manuseio dos produtos alimentícios, isto é: espaço de cozinha fechado, coberto e com ventilação, com fornecimento de água corrente e sistema de captação de esgoto, exigindo-se, ainda, que esteja em perfeito estado de conservação e limpeza. Caso contrário, desobedecida a regra, será possível à ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA., à sua livre escolha, descumprida a regra, ou se recusar à prestação de serviços, suspendendo-a até que o vício seja sanado, ou aplicar penalidade equivalente a 20% (vinte por cento) sobre o valor da presente avença ao <strong>Contratante</strong>.</p>
        <p><strong>§2º.</strong> A metragem do espaço acima referido será determinada de acordo com o número de convidados do evento, sendo a <strong>Contratante</strong> a responsável pela estrutura e, caso se faça necessário, aumento, obedecidas, por óbvio, as condições estabelecidas no parágrafo anterior, admitindo-se, aqui também, à <strong>Contratada</strong>, inobservada a regra, a rescisão contratual, com aplicação da penalidade prevista.</p>
        <p><strong><u>Cláusula 23.</u></strong> Na hipótese de o evento ser realizado fora das dependências da <strong>Contratada</strong>, a <strong>Contratante</strong> se obriga a fornecer um local de acordo com a regulamentação estabelecida pelos órgãos competentes, no que se refere às instalações e ambientes próprios para o manuseio e consumo de alimentos seguros (Resolução RDC Nº 216 de 15/09/2004, Resolução RDC Nº 275 DE 21/10/2202, Portaria SVS/MS Nº 326 de 30/07/1997, Portaria CVS 5 de 09/04/2013, dentre outras normas regulamentadoras do tema), isentando a <strong>Contratada</strong> de qualquer responsabilidade pelo risco de contaminação de alimentos em decorrência da inobservância ou infringência desta Cláusula.</p>
        </div>
    </div>  
    
    <div class="clause">
        <div class="clause-title">DA RESCISÃO</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 24.</u></strong> O presente Contrato poderá ser rescindido, pela <strong>Contratada</strong>, a qualquer tempo, independentemente de justificação ou interpelação judicial ou extrajudicial, caso a <strong>Contratante</strong> infrinja quaisquer das cláusulas e condições ora estabelecidas.</p>
        <p><strong><u>Cláusula 25.</u></strong> O prazo máximo para a <strong>Contratante</strong> solicitar o cancelamento dos serviços contratados, <u>com a possibilidade de ressarcimento de valores eventualmente pagos,</u> É DE ATÉ 11 (ONZE) DIAS ANTES DA DATA DO EVENTO; ultrapassado o referido lapso temporal, a <strong>Contratada</strong> reterá 100% (cem por cento) do valor pago.</p>
        <p><strong><u>Cláusula 26.</u></strong> Seja na hipótese de infração contratual, seja na hipótese de cancelamento da avença por parte da <strong>Contratante</strong>, ser-lhe-ão aplicados, a depender do tempo faltante para o evento, os seguintes parâmetros:</p>

        </div>
    </div>   

    <table class="summary-table" style="width: 80%; border-collapse: collapse;">
        <tr>
            <td style="border: 1px solid #000; width: 50%;"><strong>Dias Faltantes para a Execução da Prestação de Serviços Contratados</strong></td>
            <td style="border: 1px solid #000; width: 50%;"><strong>Retenção sobre o Valor Total do Contrato</strong></td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">Até 365 dias</td>
            <td style="border: 1px solid #000;">20% e eventuais perdas e danos</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 364 até 120 dias</td>
            <td style="border: 1px solid #000;">30% e eventuais perdas e danos</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 119 até 90 dias</td>
            <td style="border: 1px solid #000;">40% e eventuais perdas e danos</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 89 até 60 dias</td>
            <td style="border: 1px solid #000;">50% e eventuais perdas e danos</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 59 até 31 dias</td>
            <td style="border: 1px solid #000;">60% e eventuais perdas e danos</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 30 até 11 dias</td>
            <td style="border: 1px solid #000;">70% e eventuais perdas e danos</td>
        </tr>
        </table>

        <p><strong>Parágrafo Único.</strong> Na hipótese de remanescer crédito em favor da <strong>Contratante</strong>, por ocasião da rescisão antecipada deste Contrato, a <strong>Contratada</strong> efetuará a restituição dos valores no prazo de 10 (dez) dias úteis após a formalização do Termo de Distrato, deduzido os valores correspondentes à retenção e eventuais perdas e danos, caso exista.</p>

        <div class="clause">
        <div class="clause-title">DA FORÇA MAIOR E DO CASO FORTUITO</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 27.</u></strong> A <strong>Contratada</strong> não será responsável, perante a <strong>Contratante</strong>, por qualquer falha ou atraso no cumprimento das obrigações constantes no presente contrato, causados por casos fortuitos ou força maior nos termos do artigo 393 do Código Civil.</p>
        <p><strong><u>Cláusula 28.</u></strong> A <strong>Contratada</strong> afetada por qualquer evento de força maior ou caso fortuito comunicará o fato à <strong>Contratante</strong> imediatamente e, no menor prazo possível, esclarecendo as circunstâncias, as ações em curso para amenizar as perdas e solucionar o ocorrido, o tempo estimado de duração e tudo o mais que for necessário à compreensão do fato, suas consequências e solução.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DAS DISPOSIÇÕES GERAIS</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 29.</u></strong> No âmbito deste contrato, as partes sempre se comunicarão por escrito.</p>
        <p><strong><u>Cláusula 30.</u></strong> Qualquer alteração, modificação, complementação, ou ajuste, somente será reconhecido e produzirá efeitos legais, se incorporado ao presente contrato mediante Termo Aditivo, devidamente reconhecido e assinado pelas partes contratantes.</p>
        <p><strong><u>Cláusula 31.</u></strong> Este contrato não criará qualquer vínculo empregatício entre os empregados/prepostos da <strong>Contratada</strong> e a <strong>Contratante</strong>, obrigando-se a primeira a se for o caso, apresentar-se em Juízo e a declarar a isenção de responsabilidade da última.</p>
        <p><strong><u>Cláusula 32.</u></strong> A mera tolerância de uma das partes em relação ao cumprimento das obrigações determinadas neste Contrato não importará em renúncia, perdão, novação ou alteração da norma infringida.</p>
        <p><strong><u>Cláusula 33.</u></strong> O presente contrato tem efeito de título executivo extrajudicial, na forma do art. 784, III, do Código de Processo Civil.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DAS INFRAÇÕES</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 34.</u></strong> Ocorrendo qualquer violação às Cláusulas e condições estabelecidas neste Contrato, fica autorizada a rescisão contratual, aplicando-se, para a parte infratora, o que posto na Cláusula 26.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO FORO</div>
        <div class="clause-content">
        <p><strong><u>Cláusula 35.</u></strong> As partes contratantes elegem o foro da Comarca de Viçosa – MG, para o fim de dirimir qualquer ação judicial oriunda do presente contrato.</p>
        <p>E, por estarem assim justos e contratados, assinam este instrumento em 02 (duas) vias e 10 (dez) laudas de igual teor e para o mesmo efeito, gerando direitos e obrigações para herdeiros e/ou sucessores, na presença das testemunhas abaixo.</p>
        </div>
    </div>

    <div style="white-space: pre-line;">
    <strong>CONTRATADA:</strong><br><br><br>
    <strong>CONTRATANTE:</strong><br><br><br>
    Testemunhas:<br>
    _____________________________<span style="margin-left: 50px;"></span>_____________________________
</div>

    

    <div class="footer">
        <div class="parthenon-info">
            <p class="footer-item">Viçosa - MG, {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        </div>
    </div>


</body>

</html>