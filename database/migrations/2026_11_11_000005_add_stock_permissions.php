<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $permissions = [
        ['slug' => 'estoque-visualizar', 'name' => 'Visualizar estoque'],
        ['slug' => 'estoque-compras', 'name' => 'Lançar compras e ajustes de estoque'],
        ['slug' => 'estoque-eventos', 'name' => 'Registrar saída e retorno de estoque dos eventos'],
        ['slug' => 'estoque-limites', 'name' => 'Ver e editar limites de estoque'],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug'], 'guard_name' => 'api'],
                ['name' => $permission['name']]
            );
        }

        $permissionIds = Permission::whereIn('slug', array_column($this->permissions, 'slug'))->pluck('id');
        Role::where('name', 'Admin')->first()?->permissions()->syncWithoutDetaching($permissionIds);

        $this->forgetCache();
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['estoque-compras', 'estoque-eventos', 'estoque-limites'])->get()->each->delete();

        $this->forgetCache();
    }

    private function forgetCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
