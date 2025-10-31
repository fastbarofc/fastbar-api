<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ImportProducts extends Command
{
    protected $signature = 'import:products {file}';
    protected $description = 'Importa produtos de um CSV (com transaction e sem dependências externas)';

    public function handle()
    {
        $path = storage_path('app/imports/' . $this->argument('file'));

        if (!file_exists($path)) {
            $this->error("Arquivo não encontrado: $path");
            return Command::FAILURE;
        }

        if (($handle = fopen($path, 'r')) === false) {
            $this->error("Erro ao abrir o arquivo.");
            return Command::FAILURE;
        }

        $header = fgetcsv($handle); // primeira linha = cabeçalho
        $header = array_map('trim', $header);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = @array_combine($header, $data);
            if ($row && !empty($row['code'])) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        if (empty($rows)) {
            $this->warn('Nenhum registro válido encontrado.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            $count = 0;

            foreach ($rows as $row) {
                if (Product::where('code', $row['code'])->exists()) {
                    continue;
                }

                $unitId = $row['unit_id'] ?? 6;
                if (!is_numeric($unitId)) {
                    $unitId = 6;
                }
                $unitId = (int)$unitId;
                if ($unitId < 1 || $unitId > 6) {
                    $unitId = 6;
                }

                $value = is_numeric($row['value'] ?? null) ? (float)$row['value'] : 0;

                Product::create([
                    'id' => Str::uuid(),
                    'code' => trim($row['code']),
                    'name' => trim($row['name']),
                    'unit_id' => (int)$unitId,
                    'value' => $value,
                    'marketable' => false,
                    'display_checklist' => false,
                    'own_production' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $count++;
            }

            DB::commit();
            $this->info("Importação concluída com sucesso. {$count} produtos adicionados.");

        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("Erro durante a importação: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
