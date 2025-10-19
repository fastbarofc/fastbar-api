<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_employee')->default(false)->after('active');
        });

        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('model_id')
                    ->from('model_has_roles')
                    ->where('model_type', 'App\Models\User')
                    ->whereIn('role_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('roles')
                            ->where('name', 'admin');
                    });
            })
            ->update(['is_employee' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_employee');
        });
    }
};
