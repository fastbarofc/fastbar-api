<?php

namespace App\Console\Commands;

use App\Services\DataFixSnapshot;
use Illuminate\Console\Command;

class RevertDataFix extends Command
{
    protected $signature = 'data-fix:revert
        {name : Nome do snapshot em storage/app/data-fixes, sem o .json}
        {--force : Reverte também as linhas que mudaram depois da correção}';

    protected $description = 'Desfaz uma correção de dados a partir do snapshot JSON que ela gravou em storage/app/data-fixes.';

    public function handle(DataFixSnapshot $snapshots): int
    {
        $result = $snapshots->revert($this->argument('name'), (bool) $this->option('force'));

        foreach ($result['reverted'] as $label) {
            $this->line("revertida: {$label}");
        }

        foreach ($result['skipped'] as $label) {
            $this->warn("ignorada: {$label}");
        }

        $this->info(count($result['reverted']) . ' linha(s) revertida(s), ' . count($result['skipped']) . ' ignorada(s).');

        if ($result['archived_to']) {
            $this->line("Snapshot arquivado em {$result['archived_to']}.");

            return self::SUCCESS;
        }

        $this->warn('O snapshot continua no lugar. Confira as linhas ignoradas e, se for o caso, rode de novo com --force.');

        return self::FAILURE;
    }
}
