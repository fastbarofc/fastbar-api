<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * André, 2026-11-09: setores Motorista de van, Motorista de caminhão e Carga (sem coordenação).
 * Carga escolhe o caminhão (3/4, Toco, Trucado), que define a diária pela seção CARGAS da Tabela de valores,
 * e tem a placa do caminhão (por evento, na Quantificação).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_sectors', function (Blueprint $table) {
            if (!Schema::hasColumn('labor_sectors', 'has_coordination')) {
                $table->boolean('has_coordination')->default(true)->after('has_transport');
            }
            if (!Schema::hasColumn('labor_sectors', 'has_truck')) {
                $table->boolean('has_truck')->default(false)->after('has_coordination');
            }
        });
        Schema::table('event_labor_quantifications', function (Blueprint $table) {
            if (!Schema::hasColumn('event_labor_quantifications', 'truck_size')) {
                $table->string('truck_size', 20)->nullable()->after('transport_quantity');
            }
            if (!Schema::hasColumn('event_labor_quantifications', 'truck_plate')) {
                $table->string('truck_plate', 12)->nullable()->after('truck_size');
            }
        });

        $position = (int) DB::table('labor_sectors')->whereNull('event_id')->max('position');
        $new = [
            ['name' => 'Motorista de Van', 'color' => '#5B6C8F', 'has_truck' => false],
            ['name' => 'Motorista de Caminhão', 'color' => '#3F7D6E', 'has_truck' => false],
            ['name' => 'Carga', 'color' => '#8E6B23', 'has_truck' => true],
        ];
        foreach ($new as $s) {
            if (DB::table('labor_sectors')->whereNull('event_id')->whereRaw('LOWER(name) = ?', [mb_strtolower($s['name'])])->exists()) {
                continue;
            }
            DB::table('labor_sectors')->insert([
                'name' => $s['name'], 'color' => $s['color'], 'value_table_role' => null,
                'has_transport' => false, 'has_coordination' => false, 'has_truck' => $s['has_truck'],
                'position' => ++$position, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('labor_sectors')->whereNull('event_id')->whereIn('name', ['Motorista de Van', 'Motorista de Caminhão', 'Carga'])->delete();
        Schema::table('event_labor_quantifications', function (Blueprint $table) {
            $table->dropColumn(['truck_size', 'truck_plate']);
        });
        Schema::table('labor_sectors', function (Blueprint $table) {
            $table->dropColumn(['has_coordination', 'has_truck']);
        });
    }
};
