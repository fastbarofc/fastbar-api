<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['slug' => 'configuracoes-grupos-produtos-visualizar', 'name' => 'Visualizar Grupos de Produtos'],
            ['slug' => 'configuracoes-grupos-produtos-criar', 'name' => 'Criar Grupos de Produtos'],
            ['slug' => 'configuracoes-grupos-produtos-editar', 'name' => 'Editar Grupos de Produtos'],
            ['slug' => 'configuracoes-grupos-produtos-excluir', 'name' => 'Excluir Grupos de Produtos'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'slug' => $permission['slug'], 'guard_name' => 'api']
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
            'configuracoes-grupos-produtos-visualizar',
            'configuracoes-grupos-produtos-criar',
            'configuracoes-grupos-produtos-editar',
            'configuracoes-grupos-produtos-excluir',
        ])->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
