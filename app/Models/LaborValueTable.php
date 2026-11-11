<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Versão da Tabela de valores dos freelancers (planilha do André). A última versão é a que vale.
 * data = { title_left, title_right, columns[{label, hours, km_max, cities[{name, pink}]}],
 *          sections[{name, rows[{name, cells[{span, value}]}]}], note, jf{title, header, rows[{city, value}]} }
 */
class LaborValueTable extends Model
{
    protected $fillable = ['data', 'changes', 'note', 'user_id'];

    protected $casts = [
        'data' => 'array',
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function current(): ?self
    {
        return self::orderByDesc('id')->first();
    }

    /** Lista "onde → valor" para comparar duas versões. */
    public static function flatten(array $data): array
    {
        $out = [];
        $columns = $data['columns'] ?? [];
        $colLabel = function (int $index, int $span) use ($columns) {
            $label = fn ($i) => ($columns[$i]['label'] ?? ('Coluna ' . ($i + 1))) . ' (' . ($columns[$i]['hours'] ?? 6) . 'h)';
            return $span > 1 ? $label($index) . ' a ' . $label($index + $span - 1) : $label($index);
        };
        $out['Título'] = $data['title_left'] ?? null;
        $out['Título 8 horas'] = $data['title_right'] ?? null;
        foreach ($columns as $i => $col) {
            $out['Cidades / ' . $colLabel($i, 1)] = implode(', ', array_map(fn ($c) => ($c['name'] ?? '') . (!empty($c['pink']) ? ' (rosa)' : ''), $col['cities'] ?? []));
        }
        foreach ($data['sections'] ?? [] as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                $index = 0;
                foreach ($row['cells'] ?? [] as $cell) {
                    $span = max(1, (int) ($cell['span'] ?? 1));
                    $out[($section['name'] ?? '') . ' / ' . ($row['name'] ?? '') . ' / ' . $colLabel($index, $span)] = $cell['value'] ?? null;
                    $index += $span;
                }
            }
        }
        $out['Observação'] = $data['note'] ?? null;
        foreach ($data['jf']['rows'] ?? [] as $row) {
            $out['Tabela JF / ' . ($row['city'] ?? '')] = $row['value'] ?? null;
        }
        return $out;
    }

    /** O que mudou de $old para $new: [{field, before, after}]. */
    public static function diff(?array $old, array $new): array
    {
        $a = $old ? self::flatten($old) : [];
        $b = self::flatten($new);
        $same = fn ($x, $y) => is_numeric($x) && is_numeric($y) ? (float) $x === (float) $y : (string) ($x ?? '') === (string) ($y ?? '');
        $changes = [];
        foreach ($b as $field => $value) {
            if (!array_key_exists($field, $a)) {
                if ($value !== null && $value !== '') {
                    $changes[] = ['field' => $field, 'before' => null, 'after' => $value];
                }
            } elseif (!$same($a[$field], $value)) {
                $changes[] = ['field' => $field, 'before' => $a[$field], 'after' => $value];
            }
        }
        foreach ($a as $field => $value) {
            if (!array_key_exists($field, $b) && $value !== null && $value !== '') {
                $changes[] = ['field' => $field, 'before' => $value, 'after' => null];
            }
        }
        return $changes;
    }

    private static function norm(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        $s = preg_replace('/\(.*?\)/u', '', $s);
        $s = strtr($s, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /**
     * Valor da diária para a função (nome igual ao da linha), pela cidade (lista da coluna) ou pela distância.
     * A cidade vale em qualquer coluna (6 ou 8 horas); se ela aparece nas duas, usa a da duração do evento
     * (mais de 6 horas = 8 horas). Sem cidade na tabela: distância, senão a coluna "Local".
     * Só devolve número; texto ("MATHEUS R$250") não conta.
     */
    public function valueFor(string $role, ?string $city, ?float $km, ?float $hours = null, ?string $sectionName = null): ?float
    {
        $data = $this->data ?? [];
        $columns = $data['columns'] ?? [];
        $roleN = self::norm($role);
        $row = null;
        foreach ($data['sections'] ?? [] as $section) {
            if ($sectionName !== null && self::norm($section['name'] ?? '') !== self::norm($sectionName)) {
                continue;
            }
            foreach ($section['rows'] ?? [] as $r) {
                if (self::norm($r['name'] ?? '') === $roleN) {
                    $row = $r;
                    break 2;
                }
            }
        }
        if (!$row) {
            return null;
        }

        $wanted = ($hours !== null && $hours > 6 && collect($columns)->contains(fn ($c) => ($c['hours'] ?? 6) == 8)) ? 8 : 6;
        $col = $city ? $this->columnForCity($columns, $city, $wanted) : null;
        if ($col === null && $km !== null) {
            foreach ($columns as $i => $c) {
                if (($c['hours'] ?? 6) != $wanted) {
                    continue;
                }
                if ($c['km_max'] === null || $km <= (float) $c['km_max']) {
                    $col = $i;
                    break;
                }
            }
        }
        if ($col === null) {
            // Cidade fora da tabela e sem distância: coluna "Local" (6 horas).
            $col = collect($columns)->search(fn ($c) => ($c['hours'] ?? 6) == 6);
            if ($col === false) {
                return null;
            }
        }

        $index = 0;
        foreach ($row['cells'] ?? [] as $cell) {
            $span = max(1, (int) ($cell['span'] ?? 1));
            if ($col >= $index && $col < $index + $span) {
                return is_numeric($cell['value'] ?? null) ? (float) $cell['value'] : null;
            }
            $index += $span;
        }
        return null;
    }

    /**
     * Coluna da cidade. Aceita pequenas diferenças de escrita entre a tabela e o cadastro de cidades
     * (Lafaiete = Conselheiro Lafaiete, S. José = São José, del Rey = del Rei, Campo/Campos, Resende Aman = Resende).
     */
    private function columnForCity(array $columns, string $city, int $wanted): ?int
    {
        $target = self::looseName($city);
        $found = [];
        foreach ([0, 1] as $pass) {
            foreach ($columns as $i => $c) {
                foreach ($c['cities'] ?? [] as $ct) {
                    $name = self::looseName($ct['name'] ?? '');
                    if ($name === '') {
                        continue;
                    }
                    $match = $pass === 0
                        ? $name === $target
                        : (levenshtein($name, $target) <= 2
                            || preg_match('/(^| )' . preg_quote($name, '/') . '$/u', $target)
                            || preg_match('/^' . preg_quote($target, '/') . '( |$)/u', $name));
                    if ($match) {
                        $found[] = $i;
                    }
                }
            }
            if ($found) {
                break;
            }
        }
        if (!$found) {
            return null;
        }
        foreach ($found as $i) {
            if (($columns[$i]['hours'] ?? 6) == $wanted) {
                return $i;
            }
        }
        return $found[0];
    }

    private static function looseName(?string $s): string
    {
        $s = self::norm($s);
        $s = preg_replace('/^s\. ?/u', 'sao ', $s);
        $s = str_replace(["'", '-'], ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** Nomes das linhas de uma parte da tabela (ex.: COORDENAÇÕES). */
    public function rowNames(string $sectionName): array
    {
        foreach ($this->data['sections'] ?? [] as $section) {
            if (self::norm($section['name'] ?? '') === self::norm($sectionName)) {
                return collect($section['rows'] ?? [])->pluck('name')->filter()->values()->all();
            }
        }
        return [];
    }
}
