<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para as telas continuarem rápidas com muitos dados (eventos, parcelas, contas e histórico).
 * Não muda nenhum dado. Confere se o índice já existe antes de criar, então pode rodar mais de uma vez.
 */
return new class extends Migration
{
    /** tabela => [nome do índice => colunas] */
    private const INDEXES = [
        'events' => [
            'events_date_perf_index' => ['date'],
        ],
        'receivables' => [
            'receivables_due_date_perf_index' => ['due_date'],
            'receivables_paid_due_perf_index' => ['paid_at', 'due_date'],
            'receivables_morph_perf_index' => ['receivableable_type', 'receivableable_id'],
        ],
        'payables' => [
            'payables_due_date_perf_index' => ['due_date'],
            'payables_paid_due_perf_index' => ['paid_at', 'due_date'],
        ],
        'event_payments' => [
            'event_payments_date_perf_index' => ['date'],
        ],
        'audits' => [
            'audits_created_at_perf_index' => ['created_at'],
        ],
        'lead_activities' => [
            'lead_activities_happened_at_perf_index' => ['happened_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                if ($this->hasIndex($table, $name) || !Schema::hasColumns($table, $columns)) {
                    continue;
                }
                Schema::table($table, fn ($t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($indexes) as $name) {
                if ($this->hasIndex($table, $name)) {
                    Schema::table($table, fn ($t) => $t->dropIndex($name));
                }
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
    }
};
