<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'configuracoes-departamentos-visualizar' => ['slug' => 'configuracoes-estoques-visualizar', 'name' => 'Visualizar Estoques'],
            'configuracoes-departamentos-criar'      => ['slug' => 'configuracoes-estoques-criar',      'name' => 'Criar Estoques'],
            'configuracoes-departamentos-editar'     => ['slug' => 'configuracoes-estoques-editar',     'name' => 'Editar Estoques'],
            'configuracoes-departamentos-excluir'    => ['slug' => 'configuracoes-estoques-excluir',    'name' => 'Excluir Estoques'],
        ];

        foreach ($map as $oldSlug => $new) {
            // Case 1: rows where slug is still the old value
            DB::table('permissions')
                ->where('slug', $oldSlug)
                ->update($new);

            // Case 2: rows where a previous migration mistakenly wrote the new slug into the `name` column
            DB::table('permissions')
                ->where('name', $new['slug'])
                ->update(['name' => $new['name']]);
        }

        // Remove any lingering permissions that still reference "Departamentos"
        DB::table('permissions')
            ->where('slug', 'like', 'configuracoes-departamentos-%')
            ->orWhere('name', 'like', '% Departamentos')
            ->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // Intentionally no-op: this is a corrective migration.
    }
};
