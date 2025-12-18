<?php

namespace App\Console\Commands;

use App\Models\FinancialOperation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportFinancialOperations extends Command
{
    protected $signature = 'import:financial-operations {file}';
    protected $description = 'Importa operações financeiras de um CSV para financial_operations';

    public function handle()
    {
        $path = storage_path('app/imports/' . $this->argument('file'));

        if (!file_exists($path)) {
            $this->error("Arquivo não encontrado: $path");
            return Command::FAILURE;
        }

        if (($handle = fopen($path, 'r')) === false) {
            $this->error('Erro ao abrir o arquivo.');
            return Command::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('CSV vazio ou inválido.');
            return Command::FAILURE;
        }

        // Normaliza cabeçalhos: remove BOM, caracteres de controle e espaços
        $cleanHeader = array_map(function ($h) {
            $h = (string)$h;
            // remove BOM (UTF-8)
            $h = preg_replace('/^\xEF\xBB\xBF/u', '', $h);
            // remove caracteres de controle
            $h = preg_replace('/[\x00-\x1F\x7F]+/u', '', $h);
            return trim($h);
        }, $header);

        // Mapa de header normalizado -> header original
        $normalizeKey = function (string $k): string {
            $k = mb_strtolower(trim($k));
            $replacements = [
                'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
                'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                'ç' => 'c',
            ];
            $k = strtr($k, $replacements);
            // remove não alfanumérico (mantém espaço para depois colapsar)
            $k = preg_replace('/[^a-z0-9 ]/u', ' ', $k);
            // colapsa espaços
            $k = preg_replace('/\s+/', ' ', $k);
            return trim($k);
        };

        $headerIndex = [];
        $normalizedPositions = [];
        foreach ($cleanHeader as $idx => $original) {
            $norm = $normalizeKey($original);
            $headerIndex[$norm] = $original;
            $normalizedPositions[$norm] = $idx;
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            // Ajuste de colunas quando houver vírgulas não-escapadas na descrição
            $expectedCols = count($cleanHeader);
            $gotCols = count($data);
            if ($gotCols !== $expectedCols) {
                $descPos = $normalizedPositions['descricao'] ?? null;
                if ($descPos !== null) {
                    if ($gotCols > $expectedCols) {
                        // Une os campos extras na descrição
                        $extra = $gotCols - $expectedCols;
                        $slice = array_slice($data, $descPos, $extra + 1);
                        $data[$descPos] = trim(implode(',', $slice));
                        // Remove os campos fundidos
                        array_splice($data, $descPos + 1, $extra);
                    } elseif ($gotCols < $expectedCols) {
                        // Completa com strings vazias
                        $data = array_pad($data, $expectedCols, '');
                    }
                } else {
                    // Sem posição de descrição conhecida, pad/trim simples
                    if ($gotCols > $expectedCols) {
                        $data = array_slice($data, 0, $expectedCols);
                    } elseif ($gotCols < $expectedCols) {
                        $data = array_pad($data, $expectedCols, '');
                    }
                }
            }

            $row = @array_combine($cleanHeader, $data);
            if (!$row) {
                continue;
            }

            // resolve chaves canônicas a partir do mapa normalizado
            $codeKey = $headerIndex['codigo'] ?? null;
            $descriptionKey = $headerIndex['descricao'] ?? null;
            $accountKey = $headerIndex['conta'] ?? null;
            $natureKey = $headerIndex['natureza'] ?? null;
            $typeKey = $headerIndex['tipo'] ?? null;

            $code = $codeKey ? trim((string)($row[$codeKey] ?? '')) : '';
            $description = $descriptionKey ? trim((string)($row[$descriptionKey] ?? '')) : '';
            $accountPt = $accountKey ? trim((string)($row[$accountKey] ?? '')) : '';
            $naturePt = $natureKey ? trim((string)($row[$natureKey] ?? '')) : '';
            $typePt = $typeKey ? trim((string)($row[$typeKey] ?? '')) : '';

            if ($code === '' || $description === '') {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'description' => $description,
                'account' => $this->mapAccount($accountPt),
                'nature' => $this->mapNature($naturePt),
                'type' => $this->mapType($typePt),
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            $this->warn('Nenhum registro válido encontrado.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $created = 0;
            foreach ($rows as $row) {
                if (FinancialOperation::where('code', $row['code'])->exists()) {
                    continue;
                }

                FinancialOperation::create([
                    'code' => $row['code'],
                    'description' => $row['description'],
                    'account' => $row['account'],
                    'nature' => $row['nature'],
                    'type' => $row['type'],
                ]);
                $created++;
            }

            DB::commit();
            $this->info("Importação concluída. {$created} operações adicionadas.");
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Erro durante a importação: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function mapAccount(string $value): string
    {
        $normalized = $this->normalizePt($value);
        return match ($normalized) {
            'sintetico' => 'synthetic',
            'analitico' => 'analytic',
            default => 'analytic',
        };
    }

    private function mapNature(string $value): string
    {
        $normalized = $this->normalizePt($value);
        return match ($normalized) {
            'receita' => 'revenue',
            'despesa' => 'expense',
            default => 'revenue',
        };
    }

    private function mapType(string $value): string
    {
        $normalized = $this->normalizePt($value);
        return match ($normalized) {
            'variavel' => 'variable',
            'fixa' => 'fixed',
            default => 'variable',
        };
    }

    private function normalizePt(string $value): string
    {
        $lower = mb_strtolower(trim($value));
        $replacements = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        return strtr($lower, $replacements);
    }
}


