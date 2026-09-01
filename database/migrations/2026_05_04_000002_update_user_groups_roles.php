<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Grupos de usuários usados pela Fast Bar. O grupo "Client" deixa de existir,
     * o acesso do portal do cliente continua sendo definido pela flag is_employee.
     */
    private array $roles = [
        ['name' => 'Financeiro', 'slug' => 'financeiro'],
        ['name' => 'Planejamento', 'slug' => 'planejamento'],
        ['name' => 'Comercial', 'slug' => 'comercial'],
        ['name' => 'Coordenadores', 'slug' => 'coordenadores'],
        ['name' => 'Freelancer', 'slug' => 'freelancer'],
    ];

    public function up(): void
    {
        foreach ($this->roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                ['slug' => $role['slug']]
            );
        }

        Role::where('name', 'Client')->where('guard_name', 'api')->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Role::firstOrCreate(
            ['name' => 'Client', 'guard_name' => 'api'],
            ['slug' => 'client']
        );

        Role::whereIn('name', array_column($this->roles, 'name'))
            ->where('guard_name', 'api')
            ->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
