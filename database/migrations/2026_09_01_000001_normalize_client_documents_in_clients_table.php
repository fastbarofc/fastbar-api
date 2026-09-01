<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove a máscara dos documentos já cadastrados e ajusta as colunas para o tamanho exato
     * do valor sem máscara: 11 dígitos no CPF e 14 caracteres no CNPJ (que pode ser alfanumérico
     * no novo formato da Receita Federal).
     */
    public function up(): void
    {
        $this->convertDocuments(fn ($document, $column) => $column === 'cpf' ? unmaskCpf($document) : unmaskCnpj($document));

        DB::statement('ALTER TABLE clients MODIFY cpf VARCHAR(11) NULL');
        DB::statement('ALTER TABLE clients MODIFY cnpj VARCHAR(14) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE clients MODIFY cpf VARCHAR(191) NULL');
        DB::statement('ALTER TABLE clients MODIFY cnpj VARCHAR(191) NULL');

        $this->convertDocuments(fn ($document, $column) => $column === 'cpf' ? maskCpf($document) : maskCnpj($document));
    }

    private function convertDocuments(callable $convert): void
    {
        DB::table('clients')
            ->select('id', 'cpf', 'cnpj')
            ->orderBy('id')
            ->chunkById(200, function ($clients) use ($convert) {
                foreach ($clients as $client) {
                    $changes = [];

                    foreach (['cpf', 'cnpj'] as $column) {
                        $converted = $convert($client->{$column}, $column);

                        if ($converted !== $client->{$column}) {
                            $changes[$column] = $converted;
                        }
                    }

                    if (!empty($changes)) {
                        DB::table('clients')->where('id', $client->id)->update($changes);
                    }
                }
            });
    }
};
