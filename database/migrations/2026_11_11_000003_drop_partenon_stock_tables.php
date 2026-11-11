<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    // Na ordem das FKs. Em produção só guardavam o seed de 15/09/2026; ver docs/estoque-legado-partenon.md.
    private const TABLES = [
        'devolution_items',
        'devolutions',
        'requisition_items',
        'requisitions',
        'purchase_order_product',
        'purchase_orders',
        'purchase_request_product',
        'purchase_requests',
        'stock_movements',
        'stock_levels',
        'stocks',
    ];

    private const PERMISSION_PREFIXES = ['compras-', 'devolucoes-', 'requisicoes-', 'configuracoes-estoques-'];

    private const MODELS = [
        'App\Models\Devolution',
        'App\Models\DevolutionItem',
        'App\Models\PurchaseOrder',
        'App\Models\PurchaseOrderProduct',
        'App\Models\PurchaseRequest',
        'App\Models\PurchaseRequestProduct',
        'App\Models\Requisition',
        'App\Models\RequisitionItem',
        'App\Models\Stock',
        'App\Models\StockLevel',
        'App\Models\StockMovement',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('audits')) {
            DB::table('audits')->whereIn('auditable_type', self::MODELS)->delete();
        }

        Permission::query()
            ->where(function ($query) {
                foreach (self::PERMISSION_PREFIXES as $prefix) {
                    $query->orWhere('slug', 'like', $prefix . '%');
                }
            })
            ->get()
            ->each->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
    }
};
