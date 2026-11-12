<?php

namespace App\Services;

/**
 * Histórico do contrato sem copiar o contrato inteiro.
 *
 * Quando o texto do contrato muda, o histórico guarda só o trecho que mudou:
 * a cláusula, o que estava escrito antes e o que ficou depois, em texto simples.
 * Vale para o histórico geral (tabela audits) e para o histórico do evento (event_histories).
 */
class ContractChangeService
{
    /** Colunas com texto de contrato => nome mostrado no histórico. */
    public const TEXT_FIELDS = [
        'contract_body' => 'Contrato',
        'contract_sections' => 'Contrato',
        'contract_text' => 'Texto do contrato',
        'body' => 'Contrato',
    ];

    /** Trechos editáveis do contrato (ContractTemplate::SECTIONS). */
    private const SECTION_LABELS = [
        'parties_closing' => 'Contrato – Qualificação das partes',
        'clauses_before_menu' => 'Contrato – Cláusulas 1ª a 20ª',
        'clauses_after_menu' => 'Contrato – Cláusulas 22ª a 40ª',
    ];

    /** Dados escolhidos na hora de gerar o contrato (contract_print_data). */
    private const PRINT_LABELS = [
        'menu_modality' => 'Contrato – Modalidade do cardápio',
        'representative_ids' => 'Contrato – Representantes que assinam',
    ];

    /** Palavras de contexto mostradas antes e depois do trecho alterado. */
    private const CONTEXT_WORDS = 8;

    /** Tamanho máximo de cada valor guardado. */
    private const MAX_CHARS = 1500;

    /** Máximo de trechos por alteração (o resto vira uma linha de aviso). */
    private const MAX_ROWS = 15;

    /** Chave usada no audit para guardar só as linhas do que mudou. */
    public const AUDIT_KEY = 'contract_changes';

    /**
     * Linhas [field, label, old, new] com só o que mudou entre dois valores de uma coluna de contrato.
     */
    public static function rows(string $field, $old, $new): array
    {
        if ($field === 'contract_sections') {
            return self::sectionRows($old, $new);
        }

        if ($field === 'contract_print_data') {
            return self::printDataRows($old, $new);
        }

        return self::textRows($field, self::TEXT_FIELDS[$field] ?? 'Contrato', $old, $new);
    }

    /**
     * Troca, nos valores de um audit (laravel-auditing), as colunas de contrato pelo que mudou.
     * Na criação/exclusão o contrato não é copiado; na alteração fica só "contract_changes".
     *
     * @param array $data   o array que o laravel-auditing vai gravar (old_values, new_values, event...)
     * @param array $fields colunas de contrato do model
     */
    public static function compactAudit(array $data, array $fields, ?callable $extraRows = null): array
    {
        $old = $data['old_values'] ?? [];
        $new = $data['new_values'] ?? [];
        $rows = [];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $old) && !array_key_exists($field, $new)) {
                continue;
            }
            if (($data['event'] ?? null) === 'updated') {
                $rows = array_merge($rows, self::rows($field, $old[$field] ?? null, $new[$field] ?? null));
            }
            unset($old[$field], $new[$field]);
        }

        if ($extraRows) {
            $rows = array_merge($rows, $extraRows());
        }

        if ($rows) {
            $new[self::AUDIT_KEY] = self::limitRows($rows);
        }

        $data['old_values'] = $old;
        $data['new_values'] = $new;

        return $data;
    }

    /**
     * Linhas "o que mudou" para mostrar na tela a partir de old/new de um audit:
     * usa "contract_changes" (registros novos) ou calcula na hora (registros antigos, que guardavam o contrato inteiro).
     * Devolve [linhas, old sem as colunas de contrato, new sem as colunas de contrato].
     */
    public static function extractForDisplay(string $event, array $old, array $new, array $fields): array
    {
        $rows = [];

        if (isset($new[self::AUDIT_KEY]) && is_array($new[self::AUDIT_KEY])) {
            $rows = $new[self::AUDIT_KEY];
        }
        unset($old[self::AUDIT_KEY], $new[self::AUDIT_KEY]);

        foreach ($fields as $field) {
            if (!array_key_exists($field, $old) && !array_key_exists($field, $new)) {
                continue;
            }
            if ($event === 'updated') {
                $rows = array_merge($rows, self::rows($field, $old[$field] ?? null, $new[$field] ?? null));
            }
            unset($old[$field], $new[$field]);
        }

        return [self::limitRows($rows), $old, $new];
    }

    // ---------------------------------------------------------------------

    private static function sectionRows($old, $new): array
    {
        $old = self::decode($old);
        $new = self::decode($new);
        $rows = [];

        $keys = array_unique(array_merge(array_keys(self::SECTION_LABELS), array_keys($old), array_keys($new)));
        foreach ($keys as $key) {
            $label = self::SECTION_LABELS[$key] ?? 'Contrato';
            $rows = array_merge($rows, self::textRows('contract_sections', $label, $old[$key] ?? null, $new[$key] ?? null));
        }

        return $rows;
    }

    private static function printDataRows($old, $new): array
    {
        $old = self::decode($old);
        $new = self::decode($new);
        $rows = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            $a = self::scalarText($old[$key] ?? null);
            $b = self::scalarText($new[$key] ?? null);
            if ($a === $b) {
                continue;
            }
            $rows[] = [
                'field' => 'contract_print_data',
                'label' => self::PRINT_LABELS[$key] ?? ('Contrato – ' . ucfirst(str_replace('_', ' ', (string) $key))),
                'old' => $a,
                'new' => $b,
            ];
        }

        return $rows;
    }

    /** Compara dois textos de contrato parágrafo a parágrafo e devolve só os trechos diferentes. */
    private static function textRows(string $field, string $label, $old, $new): array
    {
        $a = self::paragraphs(is_string($old) ? $old : (is_array($old) ? implode("\n", array_filter($old, 'is_string')) : ''));
        $b = self::paragraphs(is_string($new) ? $new : (is_array($new) ? implode("\n", array_filter($new, 'is_string')) : ''));

        if ($a === $b) {
            return [];
        }

        $rows = [];
        foreach (self::hunks($a, $b) as [$removed, $added, $context]) {
            $clause = self::clauseOf($context, $removed, $added);
            $rowLabel = $clause ? self::shortLabel($label) . ' – ' . $clause : $label;

            // Mesmo número de parágrafos dos dois lados: cada parágrafo editado vira uma linha,
            // mostrando só a parte que mudou dentro dele.
            if ($removed && count($removed) === count($added)) {
                foreach ($removed as $k => $paragraph) {
                    [$before, $after] = self::trimCommon($paragraph, $added[$k]);
                    $rows[] = ['field' => $field, 'label' => $rowLabel, 'old' => self::cut($before), 'new' => self::cut($after)];
                }
                continue;
            }

            $rows[] = [
                'field' => $field,
                'label' => $rowLabel,
                'old' => self::cut($removed ? implode("\n", $removed) : null),
                'new' => self::cut($added ? implode("\n", $added) : null),
            ];
        }

        return $rows;
    }

    /** "Contrato – Cláusulas 1ª a 20ª" vira "Contrato" quando já se sabe a cláusula. */
    private static function shortLabel(string $label): string
    {
        return str_starts_with($label, 'Contrato') ? 'Contrato' : $label;
    }

    /** HTML do editor => lista de parágrafos em texto simples. */
    private static function paragraphs(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $text = preg_replace('#<\s*br\s*/?>#i', "\n", $html);
        $text = preg_replace('#</\s*(p|div|li|h[1-6]|tr|blockquote)\s*>#i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $lines = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/[ \t]+/u', ' ', $line));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Diferença entre duas listas de parágrafos.
     * Cada trecho: [parágrafos que saíram, parágrafos que entraram, parágrafos iguais que vieram antes].
     */
    private static function hunks(array $a, array $b): array
    {
        // Parte igual do começo e do fim fica de fora (deixa a comparação leve).
        $start = 0;
        while ($start < count($a) && $start < count($b) && $a[$start] === $b[$start]) {
            $start++;
        }
        $endA = count($a);
        $endB = count($b);
        while ($endA > $start && $endB > $start && $a[$endA - 1] === $b[$endB - 1]) {
            $endA--;
            $endB--;
        }

        $midA = array_slice($a, $start, $endA - $start);
        $midB = array_slice($b, $start, $endB - $start);
        $before = array_slice($a, 0, $start);

        $n = count($midA);
        $m = count($midB);

        // Contrato muito diferente (ex.: trocado inteiro): um único trecho, sem comparação detalhada.
        if ($n * $m > 250000) {
            return [[$midA, $midB, $before]];
        }

        // Maior sequência comum (LCS) parágrafo a parágrafo.
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $midA[$i] === $midB[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $hunks = [];
        $removed = [];
        $added = [];
        $context = $before;
        $i = 0;
        $j = 0;
        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $midA[$i] === $midB[$j]) {
                if ($removed || $added) {
                    $hunks[] = [$removed, $added, $context];
                    $removed = [];
                    $added = [];
                }
                $context[] = $midA[$i];
                $i++;
                $j++;
            } elseif ($j < $m && ($i >= $n || $lcs[$i][$j + 1] >= $lcs[$i + 1][$j])) {
                $added[] = $midB[$j];
                $j++;
            } else {
                $removed[] = $midA[$i];
                $context[] = $midA[$i];
                $i++;
            }
        }
        if ($removed || $added) {
            $hunks[] = [$removed, $added, $context];
        }

        return $hunks;
    }

    /** "Cláusula 5ª" do trecho (no próprio parágrafo ou no último parágrafo de cláusula antes dele). */
    private static function clauseOf(array $context, array $removed, array $added): ?string
    {
        foreach (array_merge($removed, $added) as $line) {
            if (preg_match('/^(Cl[áa]usula\s+\d+\s*[ªºa°]?)/iu', $line, $m)) {
                return self::normalizeClause($m[1]);
            }
        }
        for ($k = count($context) - 1; $k >= 0; $k--) {
            if (preg_match('/^(Cl[áa]usula\s+\d+\s*[ªºa°]?)/iu', $context[$k], $m)) {
                return self::normalizeClause($m[1]);
            }
        }

        return null;
    }

    private static function normalizeClause(string $clause): string
    {
        preg_match('/\d+/', $clause, $n);

        return 'Cláusula ' . $n[0] . 'ª';
    }

    /**
     * Um parágrafo que mudou: tira o começo e o fim iguais e deixa algumas palavras em volta,
     * para mostrar só a parte alterada ("… valor de R$ 100,00 por …" → "… valor de R$ 120,00 por …").
     */
    private static function trimCommon(string $old, string $new): array
    {
        $a = preg_split('/\s+/u', $old);
        $b = preg_split('/\s+/u', $new);

        $p = 0;
        while ($p < count($a) && $p < count($b) && $a[$p] === $b[$p]) {
            $p++;
        }
        $s = 0;
        while ($s < count($a) - $p && $s < count($b) - $p && $a[count($a) - 1 - $s] === $b[count($b) - 1 - $s]) {
            $s++;
        }

        $from = max(0, $p - self::CONTEXT_WORDS);
        $suffixKeep = min($s, self::CONTEXT_WORDS);

        $slice = function (array $words) use ($from, $s, $suffixKeep) {
            $to = count($words) - $s + $suffixKeep;
            $text = implode(' ', array_slice($words, $from, $to - $from));
            if ($from > 0) {
                $text = '… ' . $text;
            }
            if ($to < count($words)) {
                $text .= ' …';
            }

            return trim($text) === '' ? null : $text;
        };

        return [$slice($a), $slice($b)];
    }

    private static function limitRows(array $rows): array
    {
        if (count($rows) <= self::MAX_ROWS) {
            return array_values($rows);
        }
        $extra = count($rows) - self::MAX_ROWS;
        $rows = array_slice(array_values($rows), 0, self::MAX_ROWS);
        $rows[] = [
            'field' => 'contract',
            'label' => 'Contrato',
            'old' => null,
            'new' => "… e mais {$extra} trecho(s) alterado(s)",
        ];

        return $rows;
    }

    private static function cut(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        return mb_strimwidth($text, 0, self::MAX_CHARS, '…');
    }

    private static function decode($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private static function scalarText($value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $value));
        }
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        return (string) $value;
    }
}
