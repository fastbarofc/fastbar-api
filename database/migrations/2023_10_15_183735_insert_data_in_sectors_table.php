<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Sector;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Sector::create(['name' => 'Diretoria']);
        Sector::create(['name' => 'Financeiro']);
        Sector::create(['name' => 'Contabilidade']);
        Sector::create(['name' => 'Comercial']);
        Sector::create(['name' => 'Eventos']);
        Sector::create(['name' => 'Suprimentos']);
        Sector::create(['name' => 'Produção']);
        Sector::create(['name' => 'Ingfraestrutura e Logística']);
        Sector::create(['name' => 'Administrativo']);
        Sector::create(['name' => 'Serviços']);
        Sector::create(['name' => 'Transporte']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Sector::query()->delete();
    }
};
