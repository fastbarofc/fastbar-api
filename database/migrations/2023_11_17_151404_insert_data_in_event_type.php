<?php

use App\Models\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        EventType::create(['name' => 'Aniversário']);
        EventType::create(['name' => 'Bodas']);
        EventType::create(['name' => 'Casamento']);
        EventType::create(['name' => 'Comporativo']);
        EventType::create(['name' => 'Evento Militar']);
        EventType::create(['name' => 'Eventos Diversos']);
        EventType::create(['name' => 'Formatura']);
        EventType::create(['name' => 'Licitação']);
        EventType::create(['name' => 'Locação de Salão']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        EventType::query()->delete();
    }
};
