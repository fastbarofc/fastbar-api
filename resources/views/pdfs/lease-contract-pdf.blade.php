<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Locação de Espaço para Festas e Eventos</title>

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

        .logo-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .hr-strong {
            border: 1px solid #000;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .clause {
            margin-bottom: 20px;
        }

        .clause-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        .clause-content {
            text-align: justify;
        }

        .clause-number {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div style="display: -webkit-box; -webkit-box-align: center; -webkit-box-pack: justify; margin-bottom: 20px;">
        <p><strong>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</strong></p> 
        <p><strong>Código: {{ $event->id }}</strong></p>
    </div>
    <div class="clause-title">CONTRATO DE LOCAÇÃO PARA FESTAS E EVENTOS</div>

    <p style="text-align: justify;">
        O presente instrumento particular de contrato de locação de uso de espaço com ônus tem como contratantes as seguintes partes:
    </p>

    <div style="margin-bottom: 20px;">
        <p><strong>CONTRATADO:</strong> ORGANIZAÇÃO BARATELLA E BALTAZAR EVENTOS LTDA, pessoa jurídica de direito privado, inscrita no CNPJ sob o n°: 01.005.782/0001-14, nome fantasia <em>Buffet Parthenon</em>, com sede na Rua Antônio Lopes Lelis, n°: 199, Bairro: Santo Antônio, Viçosa – MG, CEP: 36570-000, neste ato representado por André Baratela Baltazar, solteiro, CPF 071.448.506-36, RG M 120.77.640, doravante denominado <strong>LOCADOR</strong>, e;
        </p>
    </div>

    <div style="margin-bottom: 20px;">
        <p><strong>CONTRATANTE:</strong> {{ $client->name ?? 'Não informado' }}, CPF: {{ $client->cpf ?? 'Não informado' }}, RG: {{ $client->rg ?? 'Não informado' }}, residente e domiciliado no endereço: Rua: {{ $client->address['street'] ?? 'Não informado' }}, nº: {{ $client->address['number'] ?? 'Não informado' }} - Complemento: {{ $client->address['complement'] ? ', ' . $client->address['complement'] : '' }}, Bairro: {{ $client->address['district'] ?? 'Não informado' }}, CEP: {{ $client->address['zip_code'] ?? 'Não informado' }}, doravante denominado(a) <strong>LOCATÁRIO</strong>, resolvem assinar o presente CONTRATO, aplicando-se a este suas disposições irrestrita e incondicionalmente, conforme cláusulas e condições seguintes:
        </p>
    </div>

    <div class="clause">
        <div class="clause-title">OBJETO DO CONTRATO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 1ª.</span> O objeto do presente instrumento é a Locação Temporária do Salão de Festas denominado "PARTHENON", localizado à Rua Antônio Lopes Lelis 199, Bairro Santo Antônio, em Viçosa - MG, CEP 36570-000, para a realização do evento:
            <br><u>Evento</u>: {{ $event->name ?? 'Não informado' }}.<br>
            <u>Data</u>: {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}<br>
            <u>Horário</u>: {{ $event->time ?? 'Não informado' }}.<br>
            <u>Duração</u>: {{ $event->duration ?? 'Não informado' }}</p>
            <p><span class="clause-number">Cláusula 2ª.</span> O estacionamento do salão, não coberto, que comporta 120 (cento e vinte) veículos, faz parte do objeto do presente contrato; sendo de única e exclusiva responsabilidade do LOCATÁRIO o credenciamento, guarda e proteção dos veículos, autorizados a utilizar o pátio de estacionamento durante evento, isentando a LOCADORA de qualquer responsabilidade seja por furtos, roubos, ou danos causados em veículos.</p>
            <p><span class="clause-number">Cláusula 3ª.</span> O LOCATÁRIO se obriga a restituir o imóvel, objeto do presente contrato, nas mesmas condições que o recebeu, às 08:00 horas do dia seguinte, mediante vistoria feita pelas partes. Fica ressalvada a limpeza do Salão, que será realizada pela LOCADORA após o término do evento.</p>
            <p><span class="clause-number">Cláusula 3.1ª.</span> Caso o LOCATÁRIO não proceda à devolução do espaço no dia e hora designados neste contrato, pagará ao LOCADOR o valor equivalente a MAIS UMA LOCAÇÃO, cumulado com outras sanções estipuladas neste contrato.</p>
            <p><span class="clause-number">Cláusula 4ª.</span> Caberá ao LOCATÁRIO manter e zelar pela conservação de todos os utensílios e equipamentos que guarnecem o imóvel e assim restituí-los ao final, sob pena de responder civilmente pelos danos eventualmente provocados.</p>
            <p><span class="clause-number">Cláusula 5ª.</span> Fica determinado que o acionamento do Ar condicionado será realizado 3 (três) horas antecedentes ao evento e durante toda a execução do mesmo.</p>
            <p><span class="clause-number">Cláusula 5.1ª.</span> Caso seja solicitado o acionamento do ar condicionado em horário anterior ao estabelecido acima, será cobrada uma taxa no valor adicional de R$70,00 (setenta reais) por hora.</p>
            <p><span class="clause-number">Cláusula 6ª.</span> A LOCADORA fornecerá todo o material para os toaletes masculino e feminino, além de um funcionário em cada banheiro e outro para a manutenção em geral, ou seja, o total de 03 (três) funcionários.</p>
            <p><span class="clause-number">Cláusula 7ª.</span> A área locada destina-se exclusivamente à finalidade de realização do evento informado no preâmbulo, ficando expressamente proibida a cessão ou sublocação deste espaço, exceto expressa autorização do locador.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO PREÇO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 8ª.</span> O valor da presente locação é de R$ {{ number_format($event->lease, 2, ',', '.') }}, que será pago da seguinte forma:</p>
            <p><span class="clause-number">Cláusula 8.1.</span> O pagamento será realizado de forma à vista, em dinheiro, no ato da assinatura deste contrato OU O pagamento será realizado de forma à vista, mediante a emissão de cheque nominal e não à ordem à <strong>ORGANIZAÇÃO BARATELLA E BALTAZAR LTDA. - EPP</strong>, devidamente cruzado, no ato da assinatura deste contrato.</p>
            <p><span class="clause-number">Cláusula 9ª.</span> Os pagamentos não realizados ou qualquer pagamento fora da data acima pactuada sofrerá acréscimo de multa de 10% (dez por cento) e juros de mora de 1% ao mês, e ficarão condicionados à confirmação da data pactuada.</p>
            <p><span class="clause-number">Cláusula 10ª.</span> Caso, no dia da locação, o imóvel não esteja disponível para a realização do evento, por motivos de embargo do Poder Público ou motivo de força maior e caso fortuito, haverá rescisão imediata de contrato e devolução integral da quantia paga pelo LOCATÁRIO, não sendo devida nenhuma multa por este motivo.</p>
            <p><span class="clause-number">Cláusula 11ª.</span> O não pagamento do valor estipulado neste documento, implica no cancelamento imediato deste contrato, sem o direito de ressarcimento pela LOCADORA após o término do evento.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DA VISTORIA</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 12ª.</span> Por ocasião da entrega e devolução da área, LOCADORA e LOCATÁRIO verificarão através do auto de vistoria feita no início da montagem e no término da desmontagem se o espaço está nas condições ajustadas neste contrato, tudo devendo ser entregue da forma convencionada. As partes deverão designar um responsável pela elaboração da vistoria.</p>
            <p><span class="clause-number">Cláusula 13ª.</span> A LOCADORA poderá certificar-se do estado de conservação das dependências sempre que achar necessário, tendo permissão de entrar com até 3 (três) representantes, independentemente do horário, até mesmo durante a realização do evento.</p>
            <p><span class="clause-number">Cláusula 14ª.</span> Deverá a LOCADORA entregar o espaço limpo e em bom estado de conservação possibilitando a utilização para as atividades a serem nele desenvolvidas.</p>
            <p><span class="clause-number">Cláusula 15ª.</span> É proibida qualquer alteração ou modificação na estrutura do imóvel locado, excetuados apenas as que sejam removíveis, que deverão ser retiradas por ocasião da entrega do imóvel locado, sendo proibido colar, fixar ou pregar qualquer tipo de objetos ou papel nas paredes e tetos. O LOCATÁRIO será responsável por qualquer danificação na pintura ou no piso.</p>
            <p><span class="clause-number">Cláusula 16ª.</span> Quaisquer instalações de equipamento, montagens, infraestrutura do evento, bem como as contratações que se fizerem necessárias, sejam de pessoas, equipamentos, decoração, conforme normas técnicas pertinentes ao assunto ocorrerão por conta exclusiva do LOCATÁRIO, isentando a LOCADORA de quaisquer ônus que por ventura recaiam sobre o ELA e que digam respeito à ocupação da área durante o período de contrato.</p>
            <p><span class="clause-number">Cláusula 17ª.</span> A LOCADORA entregará o salão com a capacidade máxima de 200 KW para ser utilizado interna e externamente, não se responsabilizando pelo que exceder, devendo o LOCATÁRIO se prevenir com antecedência contratando, caso julgue necessário, geradores. A LOCADORA em nenhuma hipótese será responsabilizada pela falta de energia elétrica, que possa vir a prejudicar o evento.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DAS RESPONSABILIDADES - SEGURANÇA</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 18ª.</span> O LOCATÁRIO se responsabiliza pela segurança, controle e fiscalização das pessoas com acesso ao evento, bem como observar a legislação que proíbe o consumo e a venda de bebidas alcoólicas para menores de idade durante todo período de vigência do contrato, conforme portarias da Vara da Infância e Juventude da Comarca de Viçosa.</p>
            <p><span class="clause-number">Cláusula 19ª.</span> O LOCATÁRIO se obriga a satisfazer todas as exigências do poder público necessárias ao uso do estabelecimento tais como Prefeitura Municipal, Juizado de Menores, ECAD, Polícia Militar, CREA e <u>contratar Empresa de Segurança devidamente qualificada e certificada</u>, na proporção de 01 (um) profissional para cada 50 (cinquenta) convidados, no máximo 24 (vinte e quatro) horas antes da data prevista para o uso do estabelecimento, sob pena da LOCADORA não permitir a realização do evento.</p>
            <p><span class="clause-number"><u>Parágrafo Único: </u></span> O LOCATÁRIO deverá repassar à LOCADORA, imediatamente após a contratação da equipe de segurança, todas as informações concernentes ao quadro de pessoal, inclusive com a especificação do número de segurnaças que atenderá o local, sob pena de não ser permitida a realização do evento. </p>
            <p><span class="clause-number">Cláusula 20ª.</span> É de total obrigação de o LOCATÁRIO manter o evento dentro das determinações contidas na Resolução do CONAMA 01/90, que dispõe sobre a poluição sonora, ou seja, não será permitido ruído sonoro superior a 70 (setenta) decibéis durante o dia e 60 (sessenta) decibéis durante o período noturno compreendido entre as 22 horas e 05 horas, não tendo a LOCADORA qualquer responsabilidade caso o limite previsto seja ultrapassado, e acarrete multas ou intervenção das autoridades competentes.</p>
            <p><span class="clause-number"><u>Parágrafo Único: </u></span> Caso o LOCATÁRIO não observe às determinações do CONAMA, conforme exposto no caput desta cláusula, responderá por todos os prejuízos e danos causados a vizinhos e terceiros, inclusive perante às esferas adminsitrativa e judicial, quando for o caso.</p>
            <p><span class="clause-number">Cláusula 21ª.</span> A LOCADORA <strong><u>não</u></strong> se responsabiliza por objetos de valor deixados, esquecidos ou perdidos no local do evento assim como materiais de prestadores de serviços (som, iluminação, decoração etc.) que não forem retirados após o evento.</p>
            <p><span class="clause-number">Cláusula 22ª.</span> É de única e exclusiva responsabilidade do LOCATÁRIO a guarda e vigilância de todo e qualquer equipamento e utensílios que vierem a ser instalados e utilizados no interior do espaço.</p>
            <p><span class="clause-number">Cláusula 23ª.</span> O LOCATÁRIO se declara responsável por quaisquer danos decorrentes da montagem ou desmontagem de equipamentos causado no espaço ora locado.</p>
            <p><span class="clause-number">Cláusula 24ª.</span> É de única e exclusiva responsabilidade do LOCATÁRIO a segurança do evento como um todo, material utilizado, segurança dos equipamentos e, principalmente, danos causados a terceiros (física ou material).</p>
            <p><span class="clause-number">Cláusula 25ª.</span> Será de responsabilidade do LOCATÁRIO o pagamento dos salários, encargos previdenciários, fundiários, acidentes e fiscalização do trabalho dos profissionais por ele contratados (inclusive os terceirizados) na utilização do evento, assim como será de sua responsabilidade qualquer acidente ocorrido nas dependências do imóvel.</p>
            <p><span class="clause-number">Cláusula 26ª.</span> O LOCATÁRIO se responsabiliza ampla e irrestritamente pelos seus convidados, os quais serão recepcionados no evento mediante a apresentação de convite oficial fornecido pelo LOCATÁRIO, ficando o LOCADOR isento de quaisquer responsabilidades por eventuais fraudes e/ou falsificações de convites.</p>
            <p><span class="clause-number">Cláusula 27ª.</span> A limpeza e retirada do lixo são de responsabilidade da LOCADORA.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DAS OBRIGAÇÕES DO LOCATÁRIO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 28ª.</span> O LOCATÁRIO obriga-se a:</p>
            <p><strong>28.1.</strong> Não fazer obras, modificações ou instalações sem prévia aprovação e consentimento por escrito da LOCADORA e, quando feitas, o LOCATÁRIO não poderá, em caso algum, invocar o direito de retenção por benfeitorias e nem pedir indenização. O LOCATÁRIO também se compromete a repor tudo nas condições primitivas, conforme a vistoria prévia a ser realizada 24 horas antes do início da montagem.</p>
            <p><strong>28.2.</strong> Para efeito de indenização, qualquer dano ao imóvel deverá ser corrigido pelo valor do IGP-M/FGV correspondente entre a data de assinatura do contrato e o da efetiva indenização.</p>
            <p><strong>28.3.</strong> O LOCATÁRIO não praticará nenhum ato que prejudique os vizinhos ou possa afetar a reputação, bom nome e conceito da LOCADORA.</p>
            <p><strong>28.4.</strong> O LOCATÁRIO se obriga a afixar em local visível e de fácil acesso, na entrada do público, informação destacada sobre a natureza do evento e a faixa etária específica de acordo com o certificado de classificação próprio, emitido pelo Juizado de Infância e Juventude, proibindo a entrada e a permanência no espaço cedido de pessoas fora da faixa etária permitida.</p>
            <p><strong>28.5.</strong> O LOCATÁRIO desde já se declara o único responsável pela entrada e permanência de pessoas fora da faixa etária permitida, bem como responsável por quaisquer danos, sejam eles cíveis, criminais ou de qualquer natureza porventura ocorrido.</p>
            <p><strong>28.6.</strong> Os profissionais e/ou empresas contratados para efetuarem a prestação dos serviços de banda, DJ, som, iluminação, ornamentação e decoração se comprometerão, mediante a assinatura e anuência do TERMO DE RESPONSABILIDADE (anexo ao contrato), a observarem rigorosamente as normas internas do local do evento.</p>
            <p><strong>28.7.</strong> O LOCATÁRIO se compromete a obedecer e seguir as normas legais de segurança do trabalho, tanto no planejamento e na execução dos serviços para a realização do evento.</p>
            <p><strong>28.8.</strong> Caso a LOCADORA tenha que entregar o espaço para terceiros e o local não tenha sido liberado pelo LOCATÁRIO, a LOCADORA fica autorizada a remover o material e/ou equipamentos remanescentes para lugar de sua escolha, sendo cobrada posteriormente a fatura referente à mão de obra, locação e transporte utilizado para a remoção. Valor este que, desde já, o LOCATÁRIO concorda em reembolsar.</p>
            <p><strong>28.9.</strong> A responsabilidade pelo bem estar e integridade física dos frequentadores do evento, bem como a fiscalização quanto a restrição da venda de bebidas alcoólicas a menores de idade, será exclusivamente do LOCATÁRIO, resguardando o direito de regresso da LOCADORA em caso de eventual responsabilização em processos administrativos e/ou judiciais.</p>
            <p><strong>28.10.</strong> É de responsabilidade do LOCATÁRIO, em caso de danos causados às dependências da LOCADORA e/ou sua imagem como também a de seus usuários e terceiros, indenizar a LOCADORA por tais danos, sejam eles causados por seus representantes, empregados e terceiros convidados.</p>
            <p><strong>28.11.</strong> É EXPRESSAMENTE proibida a utilização de chuva de prata, serpentina e confetes na área interna e externa do salão, fixar pregos nas paredes e/ou outras superfícies, bem como adesivar qualquer superfície do salão, inclusive o piso. No entanto, fica autorizado o uso de tablados adesivados pelo LOCATÁRIO.</p>
            <p><strong>28.12.</strong> Todos os comprovantes especificados deverão ser apresentados em até 05 (cinco) dias úteis antes da montagem do evento, sob pena de cancelamento imediato do contrato e/ou impedimento de ter acesso à área contratada para a montagem e realização do evento.</p>
            <p><strong>28.13.</strong> É proibida a permanência de carros com aparelho de som ligado próximo ao recinto após o encerramento do evento.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DA RESCISÃO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 29ª.</span> A desistência do contrato só será aceita de acordo com os prazos e as taxas a seguir descritos, devendo ser formalizado por comunicação escrita diretamente à LOCADORA:</p>
            <table class="summary-table">
            <tr>
                <th style="border: 1px solid #000; text-align: center;">DIAS FALTANTES PARA A EXECUÇÃO DA PRESTAÇÃO DE SERVIÇOS CONTRATADOS</th>
                <th style="border: 1px solid #000; text-align: center;">MULTA SOBRE O VALOR TOTAL DO CONTRATO</th>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">ATÉ 365 DIAS</td>
                <td style="border: 1px solid #000; text-align: center;">20%</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">DE 364 DIAS ATÉ 200 DIAS</td>
                <td style="border: 1px solid #000; text-align: center;">30%</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">DE 199 DIAS ATÉ 90 DIAS</td>
                <td style="border: 1px solid #000; text-align: center;">50%</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center;">MENOS DE 90 DIAS</td>
                <td style="border: 1px solid #000; text-align: center;">100%</td>
            </tr>
        </table>
        <br>
            <p><span class="clause-number">Cláusula 29.1.</span> Após o prazo de 90 (noventa) dias, caso ocorra a desistência o LOCATÁRIO não terá direito a qualquer devolução, servindo a penalidade para reparar o prejuízo pela não locação do imóvel.</p>
            <p><span class="clause-number">Cláusula 30ª.</span> O prazo para a devolução de possível restituição do valor ocorrerá em até 20 (vinte) dias úteis após a data do cancelamento.</p>
            <p><span class="clause-number">Cláusula 31ª.</span> Não será admitido em nenhuma hipótese qualquer pedido de transferência de data para a realização do evento ora contratado. Em sendo de interesse exclusivo da LOCADORA e havendo disponibilidade de datas em sua agenda, o LOCATÁRIO poderá requerer a transferência da data do evento contratado arcando com uma multa no valor de 10% (dez por cento) do valor da presente locação, sendo exigida ainda autorização específica da LOCADORA para que tal transferência seja efetivada.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DISPOSIÇÕES GERAIS</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 32ª.</span> A LOCADORA poderá fotografar os eventos prontos, como também divulgar o nome e imagem do LOCATÁRIO e de seus convidados, livre de quaisquer ônus, para fins publicitários e de propaganda. O LOCATÁRIO autoriza a divulgação das imagens gratuitamente.</p>
            <p><span class="clause-number">Cláusula 33ª.</span> Fica o LOCATÁRIO autorizado a realizar o ensaio fotográfico nas dependências do "PARTHENON" a título de cortesia no período mencionado no contrato.</p>
            <p><span class="clause-number">Cláusula 34ª.</span> O presente contrato obrigará as partes por si, seus herdeiros e/ou sucessores.</p>
            <p><span class="clause-number">Cláusula 35ª.</span> O locatário deverá responder civil e criminalmente por todo e qualquer ato e fato ocorrido nas dependências da contratada em razão do evento.</p>
        </div>
    </div>

    <div class="clause">
        <div class="clause-title">DO FORO DE ELEIÇÃO</div>
        <div class="clause-content">
            <p><span class="clause-number">Cláusula 36ª.</span> Fica eleito o foro da Comarca de Viçosa – MG, com exclusão de qualquer outro por privilegiado que seja como competente para conhecer e dirimir quaisquer dúvidas oriundas deste contrato.</p>
            <p>E por estarem assim justas e contratadas, assinam as partes o presente instrumento em 02 (duas) vias de igual teor e forma, na presença das testemunhas abaixo.</p><br>
            <p style="text-align: right;">{{ \Carbon\Carbon::now()->translatedFormat('l, d \d\e F \d\e Y') }}</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 50px;">
    <p>________________________________________</p>
    <p>{{ $client->name ?? 'Não informado' }}</p>
    <p>CPF: {{ $client->cpf ?? 'Não informado' }}</p>
    <p>RG: {{ $client->rg ?? 'Não informado' }}</p>
    <p>CONTRATANTE</p>

    <p style="margin-top: 50px;">________________________________________</p>
    <p>Organização Baratela e Baltazar Ltda</p>
    <p>CNPJ: 01.005.782/0001-14</p>
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