<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * E-mail do colaborador deixa de ser obrigatório.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE collaborators MODIFY email VARCHAR(255) NULL');
        DB::table('collaborators')->where('email', '')->update(['email' => null]);
    }

    public function down(): void
    {
        DB::table('collaborators')->whereNull('email')->update(['email' => '']);
        DB::statement('ALTER TABLE collaborators MODIFY email VARCHAR(255) NOT NULL');
    }
};
