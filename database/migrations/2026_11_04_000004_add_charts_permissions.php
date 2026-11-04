<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $permissions = [
        ['slug' => 'graficos-financeiro', 'name' => 'Visualizar gráficos financeiros'],
        ['slug' => 'graficos-comercial', 'name' => 'Visualizar gráficos comerciais'],
    ];

    public function up(): void
    {
        // Renomeia em vez de recriar para que os grupos que já tinham "Visualizar Gráficos" mantenham o acesso.
        Permission::where('slug', 'graficos-visualizar')
            ->update(['slug' => 'graficos-financeiro', 'name' => 'Visualizar gráficos financeiros']);

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(
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
        Permission::where('slug', 'graficos-comercial')->get()->each->delete();

        Permission::where('slug', 'graficos-financeiro')
            ->update(['slug' => 'graficos-visualizar', 'name' => 'Visualizar Gráficos']);

        $this->forgetCache();
    }

    private function forgetCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
