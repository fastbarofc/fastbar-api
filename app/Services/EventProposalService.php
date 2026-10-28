<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

/**
 * Monta os dados da proposta comercial (Fast Bar).
 *
 * A proposta nao e persistida: o que sai daqui e apenas o preenchimento
 * inicial do formulario. O usuario ajusta o que faltar, marca os blocos
 * opcionais que quer no PDF e o payload que ele devolver e o que vai para o
 * PDF.
 */
class EventProposalService
{
    public const DEFAULT_VALIDITY_DAYS = 30;

    /**
     * Altura util da area de conteudo da pagina de orcamento, em px a 96dpi.
     * A arte reserva o topo para o cabecalho e a base para o filete: das
     * 793px (210mm em paisagem) sobram de 27,5% a 91% da pagina.
     */
    private const CONTENT_HEIGHT = 500.0;

    /** Altura do "RESUMO:" que se repete no topo da coluna da direita. */
    private const SUMMARY_HEADER_HEIGHT = 26.0;

    /** Largura aproximada de cada coluna em caracteres, Arial 13px. */
    private const LEFT_CHARS_PER_LINE = 66;
    private const RIGHT_CHARS_PER_LINE = 57;

    /** Altura de uma linha de texto de 13px com line-height 1.45. */
    private const LINE_HEIGHT = 19.0;

    /** Altura de um h2 de 15px com a margem inferior de 8px. */
    private const TITLE_HEIGHT = 26.0;

    /** Respiro entre blocos (h2.spaced). */
    private const BLOCK_GAP = 14.0;

    /**
     * Blocos do resumo, na ordem do PDF. A chave gera os campos `<chave>_items`
     * e `<chave>_title` (titulo editavel, com `title` como padrao); os blocos
     * com `priced` tambem tem `<chave>_price`, o valor por pessoa.
     */
    public static function summarySections(): array
    {
        return [
            'drinks_bar' => ['title' => 'BAR DE DRINKS:', 'priced' => true],
            'optional_drinks' => ['title' => 'BEBIDAS OPCIONAIS:', 'priced' => false],
            'spirits_bar' => ['title' => 'BAR DE DESTILADOS:', 'priced' => true],
            'fast_vip' => ['title' => 'FAST VIP:', 'priced' => true],
            'fast_concept' => ['title' => 'FAST CONCEPT:', 'priced' => true],
            'fast_premium' => ['title' => 'FAST PREMIUM:', 'priced' => true],
            'additional' => ['title' => 'ADICIONAIS (troca):', 'priced' => false],
        ];
    }

    public static function defaults(Event $event): array
    {
        $event->loadMissing(['client', 'clients', 'eventPlace', 'eventBatches']);

        $guests = self::totalGuests($event);
        $duration = self::duration($event);

        $titles = [];
        foreach (self::summarySections() as $key => $section) {
            $titles["{$key}_title"] = $section['title'];
        }

        return $titles + [
            'proposal_number' => (string) $event->id,
            'validity_days' => self::DEFAULT_VALIDITY_DAYS,
            'client_name' => self::clientName($event),
            'event_name' => $event->name ?? '',
            'location' => $event->eventPlace?->description ?? $event->eventPlace?->name ?? '',
            'date' => $event->date ? Carbon::parse($event->date)->format('d/m/y') : '',
            'duration' => $duration ? str_pad((string) $duration, 2, '0', STR_PAD_LEFT) . 'hs' : '',
            'guests' => $guests ? (string) $guests : '',

            'budget_details' => self::budgetDetails($duration, $guests),
            'observations' => [
                'Hora adicional: +10% do valor do evento.',
                'Consulte a disponibilidade de outros rótulos de destilados.',
            ],

            'drinks_bar_items' => [
                'FAST CONCEPT – ATÉ 10 DRINKS',
                'Drinks com Smirnoff, Gilbey’s e Red Label.',
                'Bar 360º ou Lateral (Balcões inclusos)',
            ],
            'drinks_bar_price' => 28,
            'optional_drinks_items' => [
                'Aperol, Rum, Saquê, Cachaça e Espumante, inclusos de acordo com o cardápio escolhido.',
            ],

            'spirits_bar_items' => ['SMIRNOFF', 'GILBEY’S', 'RED LABEL', 'MONSTER'],
            'spirits_bar_price' => 24,

            // Os pacotes Fast entram desmarcados no formulario: o texto aqui e
            // so o esqueleto, o comercial completa antes de gerar o PDF.
            'fast_vip_items' => ['FAST VIP', 'Bar 360º ou Lateral (Balcões inclusos)'],
            'fast_vip_price' => 0,

            'fast_concept_items' => ['FAST CONCEPT', 'Bar 360º ou Lateral (Balcões inclusos)'],
            'fast_concept_price' => 0,

            'fast_premium_items' => ['FAST PREMIUM', 'Bar 360º ou Lateral (Balcões inclusos)'],
            'fast_premium_price' => 0,

            'additional_items' => [
                'ABSOLUT +R$ 4,00',
                'GORDON’S +R$ 2,00',
                'TANQUERAY +R$ 4,00',
                'OLD PAR +R$ 5,00',
                'REDBULL +R$ 6,00',
                'Bar de Gin tônica, Red Label e água de Coco (sem energético) – R$ 18,00',
            ],
        ];
    }

    /**
     * Campos da capa que o PDF nao pode sair sem. O front usa essa lista para
     * destacar o que o evento nao tinha e precisa ser digitado na hora.
     */
    public static function requiredFields(): array
    {
        return ['proposal_number', 'client_name', 'event_name', 'location', 'date', 'duration', 'guests'];
    }

    /** Campos do resumo: o PDF exige ao menos um deles marcado. */
    public static function summaryFields(): array
    {
        return array_map(fn ($key) => "{$key}_items", array_keys(self::summarySections()));
    }

    /** A coluna da direita do PDF nao pode sair vazia. */
    public static function hasSummary(array $proposal): bool
    {
        foreach (self::summaryFields() as $field) {
            if (!empty($proposal[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Distribui os blocos de orcamento e resumo em paginas.
     *
     * O wkhtmltopdf nao quebra conteudo posicionado em absoluto, entao a
     * quebra e calculada aqui: a altura de cada bloco e estimada pelo CSS da
     * pagina e o bloco que nao cabe no que sobrou vai inteiro para a pagina
     * seguinte, nunca partido no meio.
     *
     * @return array<int, array{left: array, right: array}>
     */
    public static function pages(array $proposal): array
    {
        $leftColumns = self::packColumns(
            self::budgetBlocks($proposal),
            self::LEFT_CHARS_PER_LINE,
            self::CONTENT_HEIGHT,
        );

        $rightColumns = self::packColumns(
            self::summaryBlocks($proposal),
            self::RIGHT_CHARS_PER_LINE,
            self::CONTENT_HEIGHT - self::SUMMARY_HEADER_HEIGHT,
        );

        $total = max(count($leftColumns), count($rightColumns), 1);
        $pages = [];

        for ($index = 0; $index < $total; $index++) {
            $pages[] = [
                'left' => $leftColumns[$index] ?? [],
                'right' => $rightColumns[$index] ?? [],
            ];
        }

        return $pages;
    }

    /** Coluna da esquerda: orcamento detalhado e observacoes. */
    private static function budgetBlocks(array $proposal): array
    {
        $blocks = [];

        if (!empty($proposal['budget_details'])) {
            $blocks[] = [
                'title' => 'ORÇAMENTO DETALHADO:',
                'color' => 'pink',
                'tight' => false,
                'items' => $proposal['budget_details'],
            ];
        }

        if (!empty($proposal['observations'])) {
            $blocks[] = [
                'title' => 'OBSERVAÇÕES:',
                'color' => 'blue',
                'tight' => false,
                'items' => $proposal['observations'],
            ];
        }

        return $blocks;
    }

    /** Coluna da direita: bares, bebidas opcionais, pacotes Fast e adicionais. */
    private static function summaryBlocks(array $proposal): array
    {
        $blocks = [];

        foreach (self::summarySections() as $key => $section) {
            if (empty($proposal["{$key}_items"])) {
                continue;
            }

            $block = [
                'title' => trim($proposal["{$key}_title"] ?? '') ?: $section['title'],
                'color' => 'pink',
                'tight' => true,
                'items' => $proposal["{$key}_items"],
            ];

            if ($section['priced']) {
                $block['line'] = 'Valor por pessoa: ' . self::money($proposal["{$key}_price"] ?? 0) . '.';
            }

            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * Agrupa os blocos em colunas de uma pagina cada, sem partir bloco.
     *
     * @return array<int, array>
     */
    private static function packColumns(array $blocks, int $charsPerLine, float $available): array
    {
        $columns = [];
        $current = [];
        $used = 0.0;

        foreach ($blocks as $block) {
            foreach (self::fitBlock($block, $charsPerLine, $available) as $piece) {
                $height = self::blockHeight($piece, $charsPerLine);

                if ($current && $used + $height > $available) {
                    $columns[] = $current;
                    $current = [];
                    $used = 0.0;
                }

                $current[] = $piece;
                $used += $height;
            }
        }

        if ($current) {
            $columns[] = $current;
        }

        return $columns;
    }

    /**
     * Ultimo recurso para o bloco que nao cabe nem em uma pagina vazia: parte
     * a lista repetindo o titulo, porque cortar o texto seria pior. Blocos de
     * tamanho normal voltam intactos, entao a quebra por secao inteira
     * continua sendo a regra.
     *
     * @return array<int, array>
     */
    private static function fitBlock(array $block, int $charsPerLine, float $available): array
    {
        if (self::blockHeight($block, $charsPerLine) <= $available) {
            return [$block];
        }

        $items = $block['items'] ?? [];

        // Sem lista para dividir nao ha o que reorganizar.
        if (count($items) < 2) {
            return [$block];
        }

        $chunks = [];
        $chunk = [];

        foreach ($items as $item) {
            // O valor por pessoa entra na conta de todos os pedacos para que
            // sobre espaco para ele no ultimo.
            $probe = $block;
            $probe['items'] = array_merge($chunk, [$item]);

            if ($chunk && self::blockHeight($probe, $charsPerLine) > $available) {
                $chunks[] = $chunk;
                $chunk = [];
            }

            $chunk[] = $item;
        }

        if ($chunk) {
            $chunks[] = $chunk;
        }

        $pieces = [];
        $last = count($chunks) - 1;

        foreach ($chunks as $index => $chunkItems) {
            $piece = $block;
            $piece['items'] = $chunkItems;

            if ($index > 0 && !empty($block['title'])) {
                $piece['title'] = $block['title'] . ' (cont.)';
            }

            // O valor por pessoa fecha a secao: so no ultimo pedaco.
            if ($index < $last) {
                unset($piece['line']);
            }

            $pieces[] = $piece;
        }

        return $pieces;
    }

    /** Altura estimada de um bloco, seguindo as medidas do CSS do PDF. */
    private static function blockHeight(array $block, int $charsPerLine): float
    {
        $height = self::BLOCK_GAP;

        if (!empty($block['title'])) {
            $height += self::TITLE_HEIGHT;
        }

        $itemGap = ($block['tight'] ?? false) ? 1.0 : 5.0;

        foreach ($block['items'] ?? [] as $item) {
            $height += self::textHeight((string) $item, $charsPerLine) + $itemGap;
        }

        if (!empty($block['line'])) {
            $height += self::textHeight((string) $block['line'], $charsPerLine) + 6.0;
        }

        return $height;
    }

    private static function textHeight(string $text, int $charsPerLine): float
    {
        $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));

        return $lines * self::LINE_HEIGHT;
    }

    private static function money($value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    private static function clientName(Event $event): string
    {
        if ($event->client?->name) {
            return $event->client->name;
        }

        return $event->clients->pluck('name')->filter()->implode(' e ');
    }

    private static function totalGuests(Event $event): int
    {
        $total = (int) $event->event_guests + (int) $event->parthenon_courtesies + (int) $event->partner_courtesies;

        foreach ($event->eventBatches as $eventBatch) {
            $total += (int) $eventBatch->guests;
        }

        return $total;
    }

    private static function duration(Event $event): ?int
    {
        $duration = (int) $event->duration + (int) $event->duration_after;

        return $duration > 0 ? $duration : null;
    }

    private static function budgetDetails(?int $duration, int $guests): array
    {
        $hours = $duration ? str_pad((string) $duration, 2, '0', STR_PAD_LEFT) : '__';

        return [
            "Orçamento para evento com duração de {$hours} horas.",
            $guests ? "Preço praticado para o mínimo de {$guests} pessoas." : 'Preço praticado para o mínimo de ___ pessoas.',
            "Copos de acrílico por {$hours} horas de evento.",
            'Cardápio personalizável.',
            'Gelo Incluso.',
            'Equipe e material necessário inclusos.',
            'O serviço engloba todos os itens do cardápio durante o tempo de duração do evento.',
        ];
    }
}
