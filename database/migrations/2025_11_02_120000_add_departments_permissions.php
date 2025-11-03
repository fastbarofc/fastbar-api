<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['slug' => 'configuracoes-departamentos-visualizar', 'name' => 'Visualizar Departamentos'],
            ['slug' => 'configuracoes-departamentos-criar', 'name' => 'Criar Departamentos'],
            ['slug' => 'configuracoes-departamentos-editar', 'name' => 'Editar Departamentos'],
            ['slug' => 'configuracoes-departamentos-excluir', 'name' => 'Excluir Departamentos'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => "{$permission['name']}", 'slug' => "{$permission['slug']}", 'guard_name' => 'api']
            );
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissions = Permission::whereIn('slug', [
                'configuracoes-departamentos-visualizar',
                'configuracoes-departamentos-criar',
                'configuracoes-departamentos-editar',
                'configuracoes-departamentos-excluir',
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
            'configuracoes-departamentos-visualizar',
            'configuracoes-departamentos-criar',
            'configuracoes-departamentos-editar',
            'configuracoes-departamentos-excluir',
        ])->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};

