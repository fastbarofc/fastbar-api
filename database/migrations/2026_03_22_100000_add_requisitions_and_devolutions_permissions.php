<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            // Permissoes de visualizacao de requisicao ja existe
            ['slug' => 'requisicoes-criar', 'name' => 'Criar Requisições'],
            ['slug' => 'requisicoes-editar', 'name' => 'Editar Requisições'],
            ['slug' => 'requisicoes-excluir', 'name' => 'Excluir Requisições'],

            ['slug' => 'devolucoes-visualizar', 'name' => 'Visualizar Devoluções'],
            ['slug' => 'devolucoes-criar', 'name' => 'Criar Devoluções'],
            ['slug' => 'devolucoes-editar', 'name' => 'Editar Devoluções'],
            ['slug' => 'devolucoes-excluir', 'name' => 'Excluir Devoluções'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => "{$permission['name']}", 'slug' => "{$permission['slug']}", 'guard_name' => 'api']
            );
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $slugs = array_column($permissions, 'slug');
            $permissionIds = Permission::whereIn('slug', $slugs)->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Permission::whereIn('slug', [
            'requisicoes-criar',
            'requisicoes-editar',
            'requisicoes-excluir',
            'devolucoes-visualizar',
            'devolucoes-criar',
            'devolucoes-editar',
            'devolucoes-excluir',
        ])->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
