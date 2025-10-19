<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            // Cargos
            ['slug' => 'configuracoes-cargos-visualizar', 'name' => 'Visualizar Cargos'],
            ['slug' => 'configuracoes-cargos-criar', 'name' => 'Criar Cargos'],
            ['slug' => 'configuracoes-cargos-editar', 'name' => 'Editar Cargos'],
            ['slug' => 'configuracoes-cargos-excluir', 'name' => 'Excluir Cargos'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => "{$permission['name']}", 'slug' => "{$permission['slug']}", 'guard_name' => 'api']
            );
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissions = Permission::whereIn('slug', [
                'configuracoes-cargos-visualizar',
                'configuracoes-cargos-criar',
                'configuracoes-cargos-editar',
                'configuracoes-cargos-excluir',
            ])->pluck('id');
            
            $adminRole->permissions()->syncWithoutDetaching($permissions);
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Permission::whereIn('slug', [
            'configuracoes-cargos-visualizar',
            'configuracoes-cargos-criar',
            'configuracoes-cargos-editar',
            'configuracoes-cargos-excluir',
        ])->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};

