<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('internal_name', 'Fastbar')->first();

        if (!$company) {
            return;
        }

        ContractTemplate::firstOrCreate(
            ['company_id' => $company->id],
            ['content' => $this->exampleContent()]
        );
    }

    private function exampleContent(): string
    {
        return <<<'HTML'
<h2 style="text-align: center;">CONTRATO DE PRESTA&Ccedil;&Atilde;O DE SERVI&Ccedil;OS</h2>
<p><strong>CONTRATADA:</strong> Fastbar, inscrita no CNPJ sob o n&ordm; 00.000.000/0001-00, com sede na Rua Exemplo, n&ordm; 100, doravante denominada CONTRATADA.</p>
<p><strong>CONTRATANTE:</strong> [nome do cliente], inscrito(a) no CPF/CNPJ sob o n&ordm; [documento], residente na [endereco], doravante denominado(a) CONTRATANTE.</p>
<h3>CL&Aacute;USULA 1&ordf; &ndash; DO OBJETO</h3>
<p>O presente contrato tem por objeto a presta&ccedil;&atilde;o de servi&ccedil;os de bar e atendimento pela CONTRATADA no evento realizado em [data do evento], no local [local do evento], com previs&atilde;o de [numero de convidados] convidados.</p>
<h3>CL&Aacute;USULA 2&ordf; &ndash; DOS SERVI&Ccedil;OS INCLU&Iacute;DOS</h3>
<ul>
<li>Equipe de bartenders e auxiliares dimensionada conforme o n&uacute;mero de convidados;</li>
<li>Estrutura de bar, utens&iacute;lios e insumos previstos no kit contratado;</li>
<li>Montagem e desmontagem da estrutura no local do evento.</li>
</ul>
<h3>CL&Aacute;USULA 3&ordf; &ndash; DO VALOR E DA FORMA DE PAGAMENTO</h3>
<p>Pela presta&ccedil;&atilde;o dos servi&ccedil;os, a CONTRATANTE pagar&aacute; &agrave; CONTRATADA o valor total de R$ [valor total], conforme plano de pagamento acordado entre as partes.</p>
<h3>CL&Aacute;USULA 4&ordf; &ndash; DAS OBRIGA&Ccedil;&Otilde;ES DA CONTRATADA</h3>
<p>A CONTRATADA se obriga a comparecer ao local do evento com anteced&ecirc;ncia suficiente para montagem, a manter equipe uniformizada e a cumprir as normas sanit&aacute;rias aplic&aacute;veis.</p>
<h3>CL&Aacute;USULA 5&ordf; &ndash; DAS OBRIGA&Ccedil;&Otilde;ES DA CONTRATANTE</h3>
<p>A CONTRATANTE se obriga a garantir o acesso da equipe ao local, a disponibilizar energia el&eacute;trica e &aacute;gua, e a informar altera&ccedil;&otilde;es no n&uacute;mero de convidados com anteced&ecirc;ncia m&iacute;nima de 7 (sete) dias.</p>
<h3>CL&Aacute;USULA 6&ordf; &ndash; DO CANCELAMENTO</h3>
<p>O cancelamento por parte da CONTRATANTE observar&aacute; as condi&ccedil;&otilde;es de reten&ccedil;&atilde;o previstas na proposta comercial que integra este contrato.</p>
<h3>CL&Aacute;USULA 7&ordf; &ndash; DO FORO</h3>
<p>Fica eleito o foro da comarca da sede da CONTRATADA para dirimir quaisquer d&uacute;vidas oriundas deste contrato.</p>
<p>&nbsp;</p>
<p style="text-align: center;">[cidade], [data].</p>
<p>&nbsp;</p>
<table style="width: 100%; border-collapse: collapse;" border="0">
<tbody>
<tr>
<td style="width: 50%; text-align: center;">_______________________________<br />CONTRATADA</td>
<td style="width: 50%; text-align: center;">_______________________________<br />CONTRATANTE</td>
</tr>
</tbody>
</table>
HTML;
    }
}
