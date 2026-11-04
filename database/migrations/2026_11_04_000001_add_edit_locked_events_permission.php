<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $permission = [
        'slug' => 'eventos-editar-planejamento-concluido',
        'name' => 'Editar eventos em Planejamento ou Concluído',
    ];

    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['slug' => $this->permission['slug'], 'guard_name' => 'api'],
            ['name' => $this->permission['name']]
        );

        Role::where('name', 'Admin')->first()?->permissions()->syncWithoutDetaching([$permission->id]);

        $this->forgetCache();
    }

    public function down(): void
    {
        Permission::where('slug', $this->permission['slug'])->get()->each->delete();

        $this->forgetCache();
    }

    private function forgetCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
