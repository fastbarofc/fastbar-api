<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::create(['name' => 'Developer', 'slug' => 'developer', 'guard_name' => 'api']);
        Role::create(['name' => 'Admin', 'slug' => 'admin', 'guard_name' => 'api']);
    }

    public function down(): void
    {
        Role::query()->delete();
    }
};
