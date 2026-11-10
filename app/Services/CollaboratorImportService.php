<?php

namespace App\Services;

use App\Models\BankDetail;
use App\Models\Collaborator;
use App\Models\Company;
use App\Models\SectorOffice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa colaboradores da planilha do formulário de cadastro.
 *
 * Regras (André, 09/11/2026):
 * - Repetidos pelo CPF: vale só o cadastro mais recente (data e hora).
 * - *Nome* (asterisco no início/fim) = CLT; sem asterisco = Freelancer.
 * - "Nome (Outra pessoa)" ou "Nome / Outra pessoa": a conta é de outro titular.
 * - Coluna "Operação" com Poupança/Corrente vira Tipo de conta; número vira Operação.
 * - Primeira função da coluna de setor vira o Cargo.
 */
class CollaboratorImportService
{
    private array $officeCache = [];

    /**
     * @param  array  $rows  linhas cruas vindas da planilha
     * @return array resumo (e, se $apply, grava no banco)
     */
    public function import(array $rows, bool $apply): array
    {
        $latest = [];
        $withoutCpf = 0;

        foreach ($rows as $row) {
            $parsed = $this->parseRow($row);
            if (!$parsed) {
                continue;
            }
            if (!$parsed['cpf']) {
                $withoutCpf++;
                continue;
            }
            $cpf = $parsed['cpf'];
            if (!isset($latest[$cpf]) || $parsed['date'] >= $latest[$cpf]['date']) {
                $latest[$cpf] = $parsed;
            }
        }

        $existing = Collaborator::query()
            ->whereNotNull('cpf')
            ->get(['id', 'cpf'])
            ->mapWithKeys(fn ($c) => [$this->digits($c->cpf) => $c->id])
            ->all();

        $summary = [
            'rows' => count($rows),
            'unique' => count($latest),
            'without_cpf' => $withoutCpf,
            'new' => 0,
            'updated' => 0,
            'clt' => 0,
            'other_holder' => 0,
            'without_office' => 0,
            'sample' => [],
        ];

        foreach ($latest as $cpf => $item) {
            isset($existing[$cpf]) ? $summary['updated']++ : $summary['new']++;
            if ($item['employment_type'] === 'clt') {
                $summary['clt']++;
            }
            if ($item['holder_from_name']) {
                $summary['other_holder']++;
            }
            if (!$item['office'] || !$this->officeId($item['office'])) {
                $summary['without_office']++;
            }
            if (count($summary['sample']) < 8) {
                $summary['sample'][] = [
                    'name' => $item['name'],
                    'cpf' => $this->formatCpf($cpf),
                    'employment_type' => $item['employment_type'],
                    'office' => $item['office'],
                    'bank' => trim(($item['bank_name'] ?? '') . ' ' . ($item['account_type'] ?? '')),
                    'account_holder' => $item['account_holder'],
                    'action' => isset($existing[$cpf]) ? 'atualizar' : 'novo',
                ];
            }
        }

        if (!$apply) {
            return $summary;
        }

        DB::transaction(function () use ($latest, $existing) {
            $companyId = Company::defaultId();

            foreach ($latest as $cpf => $item) {
                $officeId = $item['office'] ? $this->officeId($item['office']) : null;
                $collaborator = isset($existing[$cpf]) ? Collaborator::find($existing[$cpf]) : null;

                if ($collaborator) {
                    $update = [
                        'name' => $item['name'],
                        'employment_type' => $item['employment_type'],
                    ];
                    if ($item['phone'] && !$collaborator->phone_1) {
                        $update['phone_1'] = $item['phone'];
                    }
                    if ($officeId && !$collaborator->sector_office_id) {
                        $update['sector_office_id'] = $officeId;
                        $update['sector_id'] = $this->officeCache['sector:' . $officeId] ?? null;
                    }
                    $collaborator->update($update);
                } else {
                    $collaborator = Collaborator::create([
                        'name' => $item['name'],
                        'cpf' => $cpf,
                        'phone_1' => $item['phone'],
                        'sector_office_id' => $officeId,
                        'sector_id' => $officeId ? ($this->officeCache['sector:' . $officeId] ?? null) : null,
                        'employment_type' => $item['employment_type'],
                        'active' => true,
                    ]);
                    if ($companyId) {
                        $collaborator->companies()->syncWithoutDetaching([$companyId]);
                    }
                }

                if ($item['bank_name'] || $item['agency'] || $item['account']) {
                    $bank = BankDetail::where('bank_detailable_type', Collaborator::class)
                        ->where('bank_detailable_id', $collaborator->id)
                        ->orderBy('id')
                        ->first() ?? new BankDetail([
                            'bank_detailable_type' => Collaborator::class,
                            'bank_detailable_id' => $collaborator->id,
                        ]);
                    $bank->fill([
                        'bank_name' => $item['bank_name'],
                        'agency' => $item['agency'],
                        'account' => $item['account'],
                        'account_type' => $item['account_type'],
                        'operation' => $item['operation'],
                        'account_holder' => $item['account_holder'] ?: $item['name'],
                        'status' => 1,
                    ]);
                    $bank->save();
                }
            }
        });

        return $summary;
    }

    /**
     * Lista de carteira assinada (planilha da Juliana: admissão, colaborador, CPF, rescisão).
     * Quem está na lista sem rescisão vira CLT (e é cadastrado se ainda não existir);
     * quem tem rescisão ou não está mais na lista e era CLT volta a ser Freelancer.
     */
    public function syncClt(array $rows, bool $apply): array
    {
        $collaborators = Collaborator::query()->get(['id', 'name', 'cpf', 'employment_type']);
        $byCpf = [];
        $byName = [];
        foreach ($collaborators as $c) {
            if ($c->cpf) {
                $byCpf[$this->digits($c->cpf)] = $c;
            }
            $byName[$this->normalizeName($this->cleanName($c->name)['name'])][] = $c;
        }

        $active = [];
        $terminated = [];
        $toCreate = [];
        foreach ($rows as $row) {
            $cpf = $this->cpf($row['cpf'] ?? null);
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' && !$cpf) {
                continue;
            }
            $isTerminated = !empty($row['terminated']);

            $found = null;
            if ($cpf && isset($byCpf[$cpf])) {
                $found = $byCpf[$cpf];
            } elseif ($name !== '') {
                $candidates = $byName[$this->normalizeName($this->cleanName($name)['name'])] ?? [];
                if (count($candidates) === 1) {
                    $found = $candidates[0];
                }
            }

            if ($found) {
                $isTerminated ? $terminated[$found->id] = $found : $active[$found->id] = $found;
            } elseif (!$isTerminated && $name !== '') {
                $key = $cpf ?: $this->normalizeName($name);
                $toCreate[$key] = ['name' => $this->titleCase($this->cleanName($name)['name']), 'cpf' => $cpf];
            }
        }
        // Se a mesma pessoa aparece ativa e desligada, vale ativa.
        $terminated = array_diff_key($terminated, $active);

        $becomeClt = collect($active)->filter(fn ($c) => $c->employment_type !== 'clt')->values();
        $leaveClt = $collaborators
            ->filter(fn ($c) => $c->employment_type === 'clt' && !isset($active[$c->id]))
            ->values();

        if ($apply) {
            DB::transaction(function () use ($becomeClt, $leaveClt, $toCreate) {
                foreach ($becomeClt as $c) {
                    $c->update(['employment_type' => 'clt']);
                }
                foreach ($leaveClt as $c) {
                    $c->update(['employment_type' => 'freelancer']);
                }
                $companyId = Company::defaultId();
                foreach ($toCreate as $item) {
                    $collaborator = Collaborator::create([
                        'name' => $item['name'],
                        'cpf' => $item['cpf'],
                        'employment_type' => 'clt',
                        'active' => true,
                    ]);
                    if ($companyId) {
                        $collaborator->companies()->syncWithoutDetaching([$companyId]);
                    }
                }
            });
        }

        return [
            'in_list' => count($active),
            'become_clt' => $becomeClt->pluck('name')->all(),
            'leave_clt' => $leaveClt->pluck('name')->all(),
            'new_clt' => array_values(array_map(fn ($i) => $i['name'], $toCreate)),
        ];
    }

    private function parseRow(array $row): ?array
    {
        $rawName = trim((string) ($row['name'] ?? ''));
        if ($rawName === '') {
            return null;
        }

        $clean = $this->cleanName($rawName);
        // Nome entre parênteses (anotação do André) vale mais que a coluna do formulário.
        $holder = $clean['holder'] ?: $this->text($row['account_holder'] ?? null);

        [$accountType, $operation] = $this->accountTypeAndOperation($row['operation'] ?? null);

        return [
            'date' => $this->date($row['date'] ?? null),
            'name' => $this->titleCase($clean['name']),
            'cpf' => $this->cpf($row['cpf'] ?? null),
            'employment_type' => $clean['clt'] ? 'clt' : 'freelancer',
            'holder_from_name' => (bool) $clean['holder'],
            'bank_name' => $this->text($row['bank_name'] ?? null),
            'agency' => $this->agency($row['agency'] ?? null),
            'account' => $this->text($row['account'] ?? null),
            'account_type' => $accountType,
            'operation' => $operation,
            'account_holder' => $holder ? $this->titleCase($holder) : null,
            'phone' => $this->phone($row['phone'] ?? null),
            'office' => $this->office($row['sector'] ?? null),
        ];
    }

    /** Separa asteriscos (CLT) e o titular entre parênteses ou depois de "/". */
    private function cleanName(string $raw): array
    {
        $clt = str_contains($raw, '*');
        $name = trim(str_replace('*', '', $raw));
        $holder = null;

        if (preg_match('/\(([^)]*)\)?/u', $name, $m)) {
            $holder = trim($m[1]);
            $name = trim(preg_replace('/\([^)]*\)?/u', '', $name));
        } elseif (str_contains($name, '/')) {
            [$name, $holder] = array_map('trim', explode('/', $name, 2));
        }

        $name = trim(preg_replace('/\s+/u', ' ', $name), " -");

        return ['name' => $name, 'holder' => $holder ?: null, 'clt' => $clt];
    }

    private function accountTypeAndOperation($value): array
    {
        $text = $this->text($value);
        if ($text === null) {
            return [null, null];
        }
        $lower = Str::lower(Str::ascii($text));
        $types = [];
        if (str_contains($lower, 'corr') || $lower === 'cc' || $lower === 'cc/cp') {
            $types[] = 'Corrente';
        }
        if (str_contains($lower, 'poup') || $lower === 'cp' || $lower === 'cc/cp') {
            $types[] = 'Poupança';
        }
        if ($types) {
            return [implode(' e ', array_unique($types)), null];
        }
        $digits = $this->digits($text);
        if ($digits !== '') {
            return [null, str_pad($digits, 3, '0', STR_PAD_LEFT)];
        }
        return [null, Str::limit($text, 20, '')];
    }

    private function office($value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }
        $first = trim(explode(',', $text)[0]);
        return $first !== '' ? Str::ucfirst(Str::lower($first)) : null;
    }

    /** Nomes do formulário que correspondem a um cargo já cadastrado com outro nome. */
    private const OFFICE_ALIASES = [
        'garcom/garconete' => 'garcom',
        'coordenador' => 'coordenador de eventos',
        'portaria' => 'porteiro',
        'acougue' => 'auxiliar de acougue',
        'auxiliar de acougueiro' => 'auxiliar de acougue',
        'auxiliar do acougue' => 'auxiliar de acougue',
    ];

    /** Cargo já cadastrado com esse nome (ou apelido conhecido); sem correspondência fica sem cargo. */
    private function officeId(string $name): ?int
    {
        $key = Str::lower(Str::ascii(trim($name)));
        if (array_key_exists($key, $this->officeCache)) {
            return $this->officeCache[$key];
        }

        if (!isset($this->officeCache['__all'])) {
            $this->officeCache['__all'] = SectorOffice::all()
                ->mapWithKeys(fn ($o) => [Str::lower(Str::ascii(trim($o->name))) => $o])
                ->all();
        }
        $all = $this->officeCache['__all'];
        $office = $all[self::OFFICE_ALIASES[$key] ?? $key] ?? null;

        $this->officeCache[$key] = $office?->id;
        if ($office) {
            $this->officeCache['sector:' . $office->id] = $office->sector_id;
        }

        return $office?->id;
    }

    private function date($value): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : Carbon::create(1970);
        } catch (\Throwable) {
            return Carbon::create(1970);
        }
    }

    private function cpf($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $text = is_float($value) || is_int($value) ? number_format((float) $value, 0, '', '') : (string) $value;
        $text = preg_replace('/\.0+$/', '', trim($text));
        $digits = $this->digits($text);
        if ($digits === '' || strlen($digits) > 11) {
            return null;
        }
        return str_pad($digits, 11, '0', STR_PAD_LEFT);
    }

    private function agency($value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }
        $text = preg_replace('/\.0+$/', '', $text);
        return ctype_digit($text) && strlen($text) < 4 ? str_pad($text, 4, '0', STR_PAD_LEFT) : $text;
    }

    private function phone($value): ?string
    {
        $text = $this->text($value);
        if ($text === null) {
            return null;
        }
        $digits = $this->digits(preg_replace('/\.0+$/', '', $text));
        if (strlen($digits) > 11 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }
        return $digits !== '' ? substr($digits, 0, 20) : null;
    }

    private function text($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim(preg_replace('/\s+/u', ' ', (string) $value));
        return $text === '' ? null : Str::limit($text, 250, '');
    }

    private function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    private function formatCpf(?string $cpf): ?string
    {
        if (!$cpf || strlen($cpf) !== 11) {
            return $cpf;
        }
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    private function normalizeName(string $name): string
    {
        return Str::lower(Str::ascii(preg_replace('/\s+/u', ' ', trim($name))));
    }

    /** "maria DA silva" => "Maria da Silva". */
    private function titleCase(string $name): string
    {
        $small = ['da', 'de', 'do', 'das', 'dos', 'e'];
        $words = explode(' ', mb_strtolower(trim($name)));
        foreach ($words as $i => $w) {
            $words[$i] = ($i > 0 && in_array($w, $small, true)) ? $w : mb_convert_case($w, MB_CASE_TITLE);
        }
        return implode(' ', $words);
    }
}
