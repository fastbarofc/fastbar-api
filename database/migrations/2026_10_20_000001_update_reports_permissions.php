<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $permissions = [
        ['slug' => 'relatorios-financeiro', 'name' => 'Visualizar relatórios financeiros'],
        ['slug' => 'relatorios-comercial', 'name' => 'Visualizar relatórios comerciais'],
        ['slug' => 'relatorios-exportar', 'name' => 'Exportar relatórios em planilha'],
    ];

    // Telas de itens, mesas e falhas nunca foram implementadas.
    private array $removed = [
        ['slug' => 'relatorios-gerar-relatorio-itens', 'name' => 'Gerar relatório de itens'],
        ['slug' => 'relatorios-gerar-relatorio-mesas', 'name' => 'Gerar relatório de mesas'],
        ['slug' => 'relatorios-gerar-relatorio-falhas', 'name' => 'Gerar relatório de falhas'],
    ];

    public function up(): void
    {
        // Renomeia em vez de recriar para que os grupos que geravam o relatório de eventos mantenham o acesso.
        Permission::where('slug', 'relatorios-gerar-relatorio-eventos')
            ->update(['slug' => 'relatorios-financeiro', 'name' => 'Visualizar relatórios financeiros']);

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug'], 'guard_name' => 'api'],
                ['name' => $permission['name']]
            );
        }

        Permission::whereIn('slug', array_column($this->removed, 'slug'))->get()->each->delete();

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissionIds = Permission::whereIn('slug', array_column($this->permissions, 'slug'))->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        $this->forgetCache();
    }

    public function down(): void
    {
        Permission::whereIn('slug', ['relatorios-comercial', 'relatorios-exportar'])->get()->each->delete();

        Permission::where('slug', 'relatorios-financeiro')
            ->update(['slug' => 'relatorios-gerar-relatorio-eventos', 'name' => 'Gerar relatório de eventos']);

        foreach ($this->removed as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug'], 'guard_name' => 'api'],
                ['name' => $permission['name']]
            );
        }

        $this->forgetCache();
    }

    private function forgetCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
