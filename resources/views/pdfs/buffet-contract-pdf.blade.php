<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Prestação de Serviços de Buffet</title>

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
        }

        .clause-content {
            margin-left: 20px;
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
            <td style="border: 1px solid #000;">{{ $client->name }}, nacionalidade, estado civil, profissão, inscrito(a) no CPF sob o n. {{ $client->cpf }} e portador(a) da CI de n. X, residente e domiciliado(a) no {{ $client->address }}.</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">03</td>
            <td style="border: 1px solid #000;">Objeto</td>
            <td style="border: 1px solid #000;">Prestação de serviços de bufê para o evento especificado abaixo.</td>
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
            <td style="border: 1px solid #000;">O preço será ajustado conforme o Quadro-Resumo e as cláusulas deste contrato.</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">06</td>
            <td style="border: 1px solid #000;">Forma de Pagamento</td>
            <td style="border: 1px solid #000;">Será definida conforme acordo entre as partes.</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">07</td>
            <td style="border: 1px solid #000;">Data de Assinatura</td>
            <td style="border: 1px solid #000;">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="clause">
        <div class="clause-title">DO OBJETO</div>
        <div class="clause-content">
            <p><strong>Cláusula 1ª.</strong> O presente Contrato tem por objeto a prestação, pela <strong>Contratada</strong> à <strong>Contratante</strong>, de serviços de bufê, cuja execução dar-se-á no evento devidamente especificado no Quadro-Resumo (nome, local, data, horário, duração e número de convidados) e conforme os termos que se consagram nas regras a seguir estipuladas.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO PREÇO</div>
        <div class="clause-content">
            <p><strong>Cláusula 2ª.</strong> Pela prestação dos serviços objeto da presente avença, a  <strong>Contratante</strong> pagará à <strong>Contratada</strong> o preço ajustado, do mesmo modo como na Cláusula anterior, no Quadro-Resumo, no qual restará estabelecida, também, a forma de pagamento.</p>
            <p><strong>§1º.</strong> O preço acordado entre as partes para a execução, pela <strong>Contratada</strong>, da presente avença abrange todos os encargos decorrentes dos serviços prestados que sejam de responsabilidade da ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA., tais como encargos sociais, trabalhistas e previdenciários. Do mesmo modo, estão inclusos, no preço, os incidentes que, porventura, venham a recair sobre este Contrato ou sobre a prestação de serviços, além dos encargos fiscais e tributários e quaisquer outras despesas, diretas ou indiretas, necessárias ao seu exato cumprimento.</p>
            <p><strong>§2º.</strong> Se se exceder, no dia do evento, a duração previamente acordada entre as partes, a <strong>Contratante</strong> pagará, <strong>por hora excedida</strong>, à <strong>Contratada</strong> quantia equivalente a 10% (dez por cento) sobre o valor total deste Contrato. Na oportunidade, as partes celebrarão "TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS", no qual constarão:</p>
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
            <p><strong>Cláusula 3ª.</strong> Em caso de inadimplemento do preço na data acertada, tenha se convencionado o pagamento à vista, parcelado ou por meio de cheque, aplicar-se-á, sobre o débito, multa de 2% (dois por cento), mais juros de mora de 1% (um por cento) ao mês, contados pro rata die, e correção monetária, utilizando-se, para tanto, índice do IGPM/FGV ou qualquer outro que venha a lhe substituir.</p>
            <p><strong>Parágrafo Único.</strong> Na hipótese de ser emitido cheque sem fundos nominal não à ordem à <strong>Contratada</strong>, restará configurado, por conseguinte, o inadimplemento da parcela, incidindo, sobre o débito, as penalidades descritas no caput desta Cláusula.</p>
            <p><strong>Cláusula 4ª.</strong> Persistindo a inadimplência por período superior a 10 (dez) dias, a Contratante será notificada, por escrito, para efetuar o pagamento dos valores em atraso, no prazo de até 48 (quarenta e oito) horas, sob pena de cancelamento do presente Contrato. <u>Ocorrendo efetivamente a rescisão por inadimplência da Contratante, não haverá devolução, pela Contratada, das quantias eventualmente já quitadas.</u></p>
            <p><strong>Cláusula 5ª.</strong> A qualquer tempo, enquanto restar configurado o inadimplemento dos valores devidos, a Contratante estará sujeita às medidas judiciais de execução, acrescidas, neste caso, de todas as despesas processuais, mais honorários advocatícios no importe de 20% (vinte por cento) sobre o valor da causa, sem prejuízo de aplicação, também, das penalidades estabelecidas na Cláusula 3ª.</p>
            <p><strong>Cláusula 6ª.</strong> O valor total descrito avençado deverá estar integralmente quitado até 30 (trinta) dias antes do evento, sob pena de cancelamento imediato deste Contrato, sem o direito de ressarcimento, pela Contratada, das quantias eventualmente pagas.</p> 
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO REAJUSTE</div>
        <div class="clause-content">
            <p><strong>Cláusula 7ª.</strong> O preço contratual será atualizado, anualmente, pelo índice do IGPM/FGV, iniciando-se a respectiva periodicidade após 12 (doze) meses contados da assinatura do presente Contrato. Na falta do IGPM/FGV, este índice será substituído por outro que venha a substituí-lo em determinação a política econômica do Governo.</p>
        </div>
    </div>

    <div class="clause">
    <div class="clause-title">DOS SERVIÇOS E PROFISSIONAIS</div>
    <div class="clause-content">
        <p><strong>Cláusula 8ª.</strong> Os serviços objeto da presente avença serão executados de acordo com as instruções contidas neste Contrato e com as posteriores recomendações específicas que, porventura, venham a ser feitas pela Contratante, desde que devidamente aprovadas pela Contratada.</p>
        <p style="text-align: center; font-weight: bold; text-decoration: underline;">1. Do Horário do Evento e da Tolerância.</p>
        <p><strong>Cláusula 9ª.</strong> Ressalvada uma tolerância de 30 (trinta) minutos, o horário previsto para o início do evento deverá ser respeitado pelas partes, sob pena de ser cobrada uma multa no valor correspondente a 1 (um) salário-mínimo por cada hora de atraso.</p>
        <p><strong>Cláusula 10ª.</strong> Em nenhuma hipótese haverá a prestação dos serviços de bufê em horário anterior ao contratado.</p>
        <p style="text-align: center; font-weight: bold; text-decoration: underline;">2. Do Mobiliário.</p>
        <p><strong>Cláusula 11ª.</strong> Se necessário MOBILIÁRIO para a prestação dos serviços de bufê ora contratados, nos termos da Cláusula 21, inciso III, as peças serão oferecidas de acordo com a disponibilidade de estoque da Contratada, sendo vedada à Contratante a escolha dos referidos materiais.</p>
        <p style="text-align: center; font-weight: bold; text-decoration: underline;">2. 3. Do Aumento e da Redução do Número de Convidados.</p>
        <p><strong>Cláusula 12ª.</strong> A Contratante poderá reduzir ou aumentar o número de convidados contratados em até 30 (TRINTA) DIAS ANTES DA REALIZAÇÃO DO EVENTO, quando as partes celebrarão “TERMO ADITIVO AO CONTRATO DE PRESTAÇÃO DE SERVIÇOS”, ciente das modificações no preço ajustado, conforme as condições abaixo estipuladas:</p>
        <p style="text-align: center;"><strong>I -</strong> Para a DIMINUIÇÃO DO NÚMERO DE CONVIDADOS, seguir-se-á seguir os seguintes critérios:</p>

        <table class="summary-table">
        <tr>
            <th colspan="2" style="border: 1px solid #000;">Quadro-Resumo</th>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">Patamar de Redução de Convidados Inicialmente Contratados</td>
            <td style="border: 1px solid #000;">Acréscimo no Valor por Convidado</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">Até 5%</td>
            <td style="border: 1px solid #000;">Até 8%</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000;">De 6 a 10%</td>
            <td style="border: 1px solid #000;">12%</td>
        </tr>
    </table>
    </div>
</div>


    

    <div class="footer">
        <div class="parthenon-info">
            <p class="footer-item">Viçosa - MG, {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        </div>
    </div>
</body>

</html>