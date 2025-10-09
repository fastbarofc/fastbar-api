<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'calendario',
            'requisicoes',
            'colaboradores',
            'clientes',
            'eventos',
            'locais',
            'kits',
            'produtos',
            'parceiros',
            'fornecedores',
            'empresas',
            'relatorio-de-eventos',
            'relatorio-de-itens',
            'relatorio-de-mesas',
            'relatorio-de-falhas',
            'graficos',
            'operacao-financeira',
            'centro-de-custo',
            'compras',
            'configuracoes',
            'historico',
        ];

        foreach ($modules as $module) {
                Permission::firstOrCreate(
                    ['name' => "visualizar-{$module}", 'guard_name' => 'api']
                );
                Permission::firstOrCreate(
                    ['name' => "editar-{$module}", 'guard_name' => 'api']
                );
        }

        $this->assignPermissionsToAdmin();
    }

    private function assignPermissionsToAdmin(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();

        if (! $adminRole) {
            $this->command->warn('Role "Admin" não encontrada. Permissões não atribuídas.');
            return;
        }

        $permissions = Permission::where('guard_name', 'api')->pluck('id');
        $adminRole->permissions()->sync($permissions);
    }
}
