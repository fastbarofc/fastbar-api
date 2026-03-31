<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['slug' => 'estoque-visualizar', 'name' => 'Visualizar Estoque'],
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
        Permission::whereIn('slug', ['estoque-visualizar'])->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
