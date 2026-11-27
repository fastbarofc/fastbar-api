<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // A descrição das parcelas leva o nome do evento, que sozinho já pode ter 191 caracteres.
    public function up(): void
    {
        DB::statement('ALTER TABLE receivables MODIFY description TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE receivables MODIFY description VARCHAR(191) NOT NULL');
    }
};
