<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class RecalculateStockLevels extends Command
{
    protected $signature = 'stock:recalculate';
    protected $description = 'Recalcula todos os níveis de estoque a partir das movimentações';

    public function handle(StockService $stockService): int
    {
        $this->info('Recalculando níveis de estoque...');

        $stockService->recalculateAllStockLevels();

        $this->info('Níveis de estoque recalculados com sucesso.');

        return Command::SUCCESS;
    }
}
