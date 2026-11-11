<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Bidding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Lê o edital anexado à licitação e usa a IA (Claude) para:
 * 1) listar os lotes/itens do edital, para a equipe escolher de quais quer participar;
 * 2) escrever o resumo no modelo da empresa.
 */
class BiddingAiService
{
    public const SETTING_KEY = 'anthropic_api_key';
    public const SETTING_TEMPLATE = 'bidding_summary_template';

    private const MODEL = 'claude-opus-5-5';
    private const BETAS = ['server-side-fallback-2026-07-01'];

    public const DEFAULT_TEMPLATE = <<<'TXT'
Resumo [CIDADE-UF]

Objeto: [objeto completo, como está no edital]
Modalidade: [ex.: PREGÃO ELETRÔNICO Nº 000/2026]
Processo Adm: [Nº do processo]
Modalidade Pregão: [ex.: MENOR PREÇO POR ITEM / POR LOTE / GLOBAL]
Data da disputa: [dd/mm hh"H", ex.: 04/11 9H]
Data da execução: [dd/mm e horário de início e fim; se houver mais de um item/lote, uma linha por item: "Item 01: (Dia dd/mm/aaaa, das hhh às hhh)"]
Nº de pessoas: [quantidade de convidados; se houver mais de um item, uma linha por item]
Cidade: [CIDADE-UF]
Descrição do serviço:
[Para cada item/lote escolhido: "ITEM X - NOME DO EVENTO (data e horário) PÚBLICO ESTIMADO DE N PESSOAS" e o valor estimado do item, depois a descrição COMPLETA do item copiada do edital: cardápio por refeição, bebidas, estrutura, mobiliário, decoração, profissionais (quantidades) e horários em que a comida deve estar servida]
Duração do serviço: [horas; se houver mais de um item, uma linha por item]
Valor total inicial: [valor estimado total; se houver mais de um item, o total e depois uma linha por item; se o edital não informar, "Não informado" ou "Estimado sigiloso"]
Valor por pessoa: [valor do item dividido pelo nº de pessoas; uma linha por item]
Distância: [distância aproximada por estrada de Viçosa-MG até a cidade, em km]
Prevê degustação: [Sim/Não]
Considerações:
[o que o buffet precisa saber além do cardápio: local do evento (ou "a definir pela ordem de serviço"), antecedência de montagem, limpeza, banheiros, lixo e materiais de higiene, outros serviços incluídos (decoração, som, banda, iluminação, estruturas), forma e prazo de pagamento, visita técnica, amostra ou degustação, e os pontos que o edital NÃO responde e precisam ser perguntados ao órgão]
TXT;

    public static function isConfigured(): bool
    {
        return (bool) self::apiKey();
    }

    public static function template(): string
    {
        return AppSetting::get(self::SETTING_TEMPLATE) ?: self::DEFAULT_TEMPLATE;
    }

    private static function apiKey(): ?string
    {
        return env('ANTHROPIC_API_KEY') ?: AppSetting::getSecret(self::SETTING_KEY);
    }

    private function apiKeyOrFail(): string
    {
        $key = self::apiKey();
        if (!$key) {
            throw new RuntimeException('A IA ainda não está configurada. Informe a chave da IA em "Configurar IA".');
        }

        return $key;
    }

    /**
     * Lista os lotes do edital. Retorna [['numero' => ..., 'titulo' => ..., 'descricao' => ..., 'buffet' => bool], ...].
     */
    public function listLots(Bidding $bidding): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'lotes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'numero' => ['type' => 'string', 'description' => 'Número do lote ou item, ex.: "26"'],
                            'titulo' => ['type' => 'string', 'description' => 'Título curto do lote'],
                            'descricao' => ['type' => 'string', 'description' => 'Uma frase dizendo o que o lote pede'],
                            'buffet' => ['type' => 'boolean', 'description' => 'true se o lote envolve bar, drinks, bebidas, alimentação ou serviço de garçons/bartenders'],
                        ],
                        'required' => ['numero', 'titulo', 'descricao', 'buffet'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['lotes'],
            'additionalProperties' => false,
        ];

        $text = $this->ask(
            $bidding,
            'Liste todos os lotes deste edital (se o edital não for dividido em lotes, liste os itens principais; '
            . 'se houver um único objeto, devolva um único lote). Responda em português.',
            ['format' => ['type' => 'json_schema', 'schema' => $schema]],
            8000
        );

        $data = json_decode($text, true);
        if (!is_array($data) || !isset($data['lotes']) || !is_array($data['lotes'])) {
            throw new RuntimeException('A IA não conseguiu listar os lotes do edital. Tente de novo.');
        }

        return $data['lotes'];
    }

    /**
     * Escreve o resumo do edital no modelo, só com os lotes escolhidos (ou todos, se a lista vier vazia).
     */
    public function summarize(Bidding $bidding, array $lots = []): string
    {
        $chosen = $lots
            ? 'Resuma somente estes itens/lotes, que são os que a empresa quer disputar: ' . implode(', ', $lots) . '. Ignore os demais.'
            : 'Resuma o edital inteiro, dando mais detalhe para o que envolve buffet.';

        $prompt = <<<TXT
{$chosen}

Siga exatamente o modelo abaixo, na mesma ordem e com os mesmos títulos (em negrito, como na regra 7). Responda só com o resumo, em português.

MODELO:
{$this->templateText()}
TXT;

        return trim($this->ask($bidding, $prompt, null, 32000, $this->systemPrompt()));
    }

    /**
     * Instruções fixas + exemplos de resumos feitos pela equipe. Fica em cache entre chamadas.
     */

    /**
     * Converte o resumo (negrito ** ** e marcadores "- ") em HTML seguro para impressão/PDF.
     */
    public static function summaryHtml(?string $text): string
    {
        $lines = preg_split('/\r?\n/', (string) $text);
        $html = '';
        $inList = false;
        $inline = function (string $line): string {
            $escaped = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^[-•]\s+(.*)$/', $trimmed, $m)) {
                if (!$inList) { $html .= '<ul>'; $inList = true; }
                $html .= '<li>' . $inline($m[1]) . '</li>';
                continue;
            }
            if ($inList) { $html .= '</ul>'; $inList = false; }
            if ($trimmed === '') { continue; }
            $html .= '<p>' . $inline($trimmed) . '</p>';
        }
        if ($inList) { $html .= '</ul>'; }

        return $html;
    }

    private function systemPrompt(): array
    {
        $rules = <<<'TXT'
Você trabalha para o Fastbar, empresa de bar e drinks para eventos, e resume editais de licitação para a equipe decidir se participa e montar o preço. A equipe lê o resumo em vez do edital, então ele precisa ser completo no que importa para um buffet e fiel ao texto do edital.

Regras:
1. Copie do edital, sem resumir nem reescrever: o objeto, o cardápio (cada prato, salgado, doce e bebida), a estrutura, o mobiliário, a decoração, as quantidades de profissionais e os horários em que a comida deve estar servida. Esses detalhes definem o preço.
2. Se o edital tem mais de um item ou lote, cada um é um evento: traga Data da execução, Nº de pessoas, Duração, Valor e Valor por pessoa separados por item, e a descrição completa de cada item escolhido com seu valor estimado.
3. Calcule: Duração = horário final menos inicial; Valor por pessoa = valor estimado do item dividido pelo nº de pessoas (duas casas decimais, formato R$1.234,56). Confira a conta antes de escrever. Quando o edital não dá o valor, escreva "Não informado"; quando diz que o orçamento é sigiloso, escreva "Estimado sigiloso" e avise que o sistema não mostra o teto na disputa.
4. Distância: distância aproximada por estrada entre a sede da empresa e a cidade do evento, em km (se não souber a sede, escreva "a calcular").
5. Em Considerações, escreva o que o buffet precisa saber e que não está no cardápio: onde é o evento (ou "a definir pela ordem de serviço"); com quanta antecedência montar; se limpeza, banheiros, lixo, materiais de higiene, porteiros e segurança são por conta do buffet; se há decoração, som, banda, iluminação, palco, tendas ou outras estruturas incluídas; forma e prazo de pagamento; se exige visita técnica, amostra ou degustação; se há preferência ou exclusividade para ME/EPP; se a proposta precisa separar valores por parte (buffet, decoração, local etc.); se o BUFFET precisa fornecer o local do evento e quais requisitos ele tem (área, palco, cozinha, estacionamento, energia); se o edital fixa equipe mínima, hora de chegada e uniforme; prazos e consequências da degustação ou das amostras; se permite subcontratação; validade da proposta e vigência do contrato; reajuste; alvará sanitário ou veículo refrigerado quando exigidos; adiamentos de data anunciados no edital; e um aviso quando o valor estimado parecer baixo para o que pede. NÃO inclua lista de documentos de habilitação, certidões, atestados nem multas e penalidades: a equipe não usa isso no resumo.
6. Liste no fim das Considerações os pontos que o edital não responde e a equipe costuma precisar (ex.: onde deixar o lixo, se há cozinha, água, energia e estacionamento no local, horário de acesso para montagem). Nunca invente: se não está no edital, diga que não consta.
7. Formato, para ficar organizado como o Word da equipe: cada campo do modelo começa com o título em negrito, assim: **Objeto:** texto. Deixe uma linha em branco entre os campos. Dentro de "Descrição do serviço" e "Considerações", separe os blocos com subtítulos em negrito numa linha só (ex.: **Coquetel volante**, **Jantar**, **Estrutura e decoração**, **Equipe**, **Horários**, **Pagamento**, **Pontos a confirmar**) e liste os itens com marcador "- " (um por linha). Use só negrito (**texto**) e marcadores "- "; nada de #, tabelas ou outros símbolos. Não crie campos novos fora do modelo.

Abaixo estão resumos que a própria equipe fez para outros editais. Imite o jeito deles: mesma estrutura, mesmo nível de detalhe, mesma forma de escrever datas (dd/mm 9H) e valores.
TXT;

        $examples = trim(@file_get_contents(resource_path('ai/bidding-summary-examples.txt')) ?: '');

        if ($examples === '') {
            return [['type' => 'text', 'text' => $rules, 'cache_control' => ['type' => 'ephemeral']]];
        }

        return [
            ['type' => 'text', 'text' => $rules],
            ['type' => 'text', 'text' => $examples, 'cache_control' => ['type' => 'ephemeral']],
        ];
    }

    private function templateText(): string
    {
        return self::template();
    }

    private function ask(Bidding $bidding, string $prompt, ?array $outputConfig, int $maxTokens, ?array $system = null): string
    {
        $body = array_filter([
            'model' => self::MODEL,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => [[
                'role' => 'user',
                'content' => [$this->noticeBlock($bidding), ['type' => 'text', 'text' => $prompt]],
            ]],
            'output_config' => $outputConfig,
        ], fn ($v) => $v !== null);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKeyOrFail(),
            'anthropic-version' => '2023-06-01',
        ])->timeout(300)->post('https://api.anthropic.com/v1/messages', $body);

        if ($response->failed()) {
            $error = $response->json('error.message') ?: $response->body();
            throw new RuntimeException('A IA respondeu com erro: ' . mb_substr((string) $error, 0, 300));
        }

        if ($response->json('stop_reason') === 'refusal') {
            throw new RuntimeException('A IA não conseguiu ler este edital. Preencha o resumo manualmente.');
        }

        $text = '';
        foreach ((array) $response->json('content') as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        if ($text === '') {
            throw new RuntimeException('A IA não devolveu resposta. Tente de novo.');
        }

        return $text;
    }

    /**
     * Bloco do edital para a IA: PDF vai como documento; Word vai como texto extraído.
     * O bloco fica em cache por alguns minutos, então listar lotes e resumir em seguida custa menos.
     */
    private function noticeBlock(Bidding $bidding): array
    {
        $file = $bidding->noticeFile;
        if (!$file || !Storage::disk('public')->exists($file->path)) {
            throw new RuntimeException('Anexe o edital antes de pedir o resumo da IA.');
        }

        $contents = Storage::disk('public')->get($file->path);

        if ($file->extension === 'pdf') {
            return [
                'type' => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($contents)],
                'title' => 'Edital',
                'cache_control' => ['type' => 'ephemeral'],
            ];
        }

        $text = $file->extension === 'docx' ? $this->docxText(Storage::disk('public')->path($file->path)) : '';
        if (trim($text) === '') {
            throw new RuntimeException('Não foi possível ler o edital. Anexe o edital em PDF.');
        }

        return [
            'type' => 'document',
            'source' => ['type' => 'text', 'media_type' => 'text/plain', 'data' => $text],
            'title' => 'Edital',
            'cache_control' => ['type' => 'ephemeral'],
        ];
    }

    private function docxText(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $xml = str_replace(['</w:p>', '<w:tab/>', '<w:br/>'], ["\n", "\t", "\n"], $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
