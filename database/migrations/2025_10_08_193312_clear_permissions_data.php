<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::disableForeignKeyConstraints();

        // Limpar todas as tabelas de permissões na ordem correta para evitar erros de chave estrangeira
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        // DB::table('model_has_roles')->truncate();
        // DB::table('roles')->truncate();

        Schema::enableForeignKeyConstraints();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // Esta migration não pode ser revertida pois os dados foram perdidos
        // Se necessário, os dados devem ser restaurados de um backup
    }
};
