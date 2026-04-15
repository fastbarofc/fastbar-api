<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the old unused stocks table
        Schema::dropIfExists('stocks');

        // 2. Rename departments table to stocks
        Schema::rename('departments', 'stocks');

        // 3. Rename department_id to stock_id in purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->renameColumn('department_id', 'stock_id');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');
        });

        // 4. Rename department_id to stock_id in stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id', 'product_id']);
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('department_id', 'stock_id');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');
            $table->index(['stock_id', 'product_id']);
        });

        // 5. Rename department_id to stock_id in stock_levels
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropUnique(['product_id', 'department_id']);
            $table->dropIndex('stock_levels_department_id_index');
        });
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->renameColumn('department_id', 'stock_id');
        });
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');
            $table->unique(['product_id', 'stock_id']);
            $table->index('stock_id');
        });

        // 6. Rename department_id and destination_department_id in requisitions
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['destination_department_id']);
        });
        Schema::table('requisitions', function (Blueprint $table) {
            $table->renameColumn('department_id', 'stock_id');
            $table->renameColumn('destination_department_id', 'destination_stock_id');
        });
        Schema::table('requisitions', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');
            $table->foreign('destination_stock_id')->references('id')->on('stocks')->onDelete('restrict');
        });

        // 7. Rename department_id to stock_id in devolutions
        Schema::table('devolutions', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });
        Schema::table('devolutions', function (Blueprint $table) {
            $table->renameColumn('department_id', 'stock_id');
        });
        Schema::table('devolutions', function (Blueprint $table) {
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('restrict');
        });

        // 8. Update permissions (slug + name display)
        DB::table('permissions')->where('slug', 'configuracoes-departamentos-visualizar')->update([
            'slug' => 'configuracoes-estoques-visualizar',
            'name' => 'Visualizar Estoques',
        ]);
        DB::table('permissions')->where('slug', 'configuracoes-departamentos-criar')->update([
            'slug' => 'configuracoes-estoques-criar',
            'name' => 'Criar Estoques',
        ]);
        DB::table('permissions')->where('slug', 'configuracoes-departamentos-editar')->update([
            'slug' => 'configuracoes-estoques-editar',
            'name' => 'Editar Estoques',
        ]);
        DB::table('permissions')->where('slug', 'configuracoes-departamentos-excluir')->update([
            'slug' => 'configuracoes-estoques-excluir',
            'name' => 'Excluir Estoques',
        ]);

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // Reverse permissions
        DB::table('permissions')->where('slug', 'configuracoes-estoques-visualizar')->update([
            'slug' => 'configuracoes-departamentos-visualizar',
            'name' => 'Visualizar Departamentos',
        ]);
        DB::table('permissions')->where('slug', 'configuracoes-estoques-criar')->update([
            'slug' => 'configuracoes-departamentos-criar',
            'name' => 'Criar Departamentos',
        ]);
        DB::table('permissions')->where('slug', 'configuracoes-estoques-editar')->update([
            'slug' => 'configuracoes-departamentos-editar',
            'name' => 'Editar Departamentos',
        ]);
        DB::table('permissions')->where('slug', 'configuracoes-estoques-excluir')->update([
            'slug' => 'configuracoes-departamentos-excluir',
            'name' => 'Excluir Departamentos',
        ]);

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        // Reverse devolutions
        Schema::table('devolutions', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
        });
        Schema::table('devolutions', function (Blueprint $table) {
            $table->renameColumn('stock_id', 'department_id');
        });
        Schema::table('devolutions', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('stocks')->onDelete('restrict');
        });

        // Reverse requisitions
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropForeign(['destination_stock_id']);
        });
        Schema::table('requisitions', function (Blueprint $table) {
            $table->renameColumn('stock_id', 'department_id');
            $table->renameColumn('destination_stock_id', 'destination_department_id');
        });
        Schema::table('requisitions', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('stocks')->onDelete('restrict');
            $table->foreign('destination_department_id')->references('id')->on('stocks')->onDelete('restrict');
        });

        // Reverse stock_levels
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropUnique(['product_id', 'stock_id']);
            $table->dropIndex('stock_levels_stock_id_index');
        });
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->renameColumn('stock_id', 'department_id');
        });
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('stocks')->onDelete('restrict');
            $table->unique(['product_id', 'department_id']);
            $table->index('department_id');
        });

        // Reverse stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropIndex(['stock_id', 'product_id']);
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('stock_id', 'department_id');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('stocks')->onDelete('restrict');
            $table->index(['department_id', 'product_id']);
        });

        // Reverse purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->renameColumn('stock_id', 'department_id');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('stocks')->onDelete('restrict');
        });

        // Rename table back
        Schema::rename('stocks', 'departments');
    }
};
