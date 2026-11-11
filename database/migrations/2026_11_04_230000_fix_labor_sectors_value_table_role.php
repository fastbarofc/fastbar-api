<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A diária de cada setor vem da parte "VALOR BASE" da Tabela de valores (André, 2026-11-04):
 * Cozinha usa COZINHA, Limpeza usa LIMPEZA e os demais usam GARÇOM.
 * Antes Mesa/Copa/Portaria/Fiscal/Coordenação/Financeiro/Gestor apontavam para as linhas de
 * COORDENAÇÕES (adicional de coordenação, R$ 100/120), o que dava diárias erradas.
 * Recalcula as diárias ainda não pagas do Relatório Financeiro.
 */
return new class extends Migration {
    public function up(): void
    {
        $old = ['MESA', 'PORTARIA', 'COPA', 'FINANCEIRO', 'GESTOR DE EXPERIÊNCIA', 'FISCAL DE EVENTO', 'COORDENADOR GERAL'];
        DB::table('labor_sectors')->whereIn('value_table_role', $old)->update(['value_table_role' => 'GARÇOM']);
        DB::table('labor_sectors')->whereNull('value_table_role')->whereIn('name', ['Fastbar', 'Pizzaria'])->update(['value_table_role' => 'GARÇOM']);

        if (!class_exists(\App\Models\LaborValueTable::class) || !\Illuminate\Support\Facades\Schema::hasTable('event_labor_payments')) {
            return;
        }
        $table = \App\Models\LaborValueTable::current();
        if (!$table) {
            return;
        }
        $sectors = DB::table('labor_sectors')->pluck('value_table_role', 'id');
        $rows = DB::table('event_labor_payments')->whereNull('paid_at')->get();
        foreach ($rows as $row) {
            $event = \App\Models\Event::with('eventPlace.address.city')->find($row->event_id);
            $role = $sectors[$row->labor_sector_id] ?? null;
            if (!$event || !$role) {
                continue;
            }
            $value = $table->valueFor($role, $event->eventPlace?->address?->city?->name, null, $event->duration !== null ? (float) $event->duration : null);
            if ($value !== null) {
                DB::table('event_labor_payments')->where('id', $row->id)->update(['base_value' => $value]);
            }
        }
    }

    public function down(): void
    {
    }
};
