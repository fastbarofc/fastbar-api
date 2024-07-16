<?php

use App\Models\EventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        EventStatus::create(['name' => 'Aberto']);
        EventStatus::create(['name' => 'Pré-Reserva']);
        EventStatus::create(['name' => 'Fechado']);
        EventStatus::create(['name' => 'Planejamento']);
        EventStatus::create(['name' => 'Concluído']);
        EventStatus::create(['name' => 'Descartado']);
        EventStatus::create(['name' => 'Cancelado']);
        EventStatus::create(['name' => 'Pandemia']);
        EventStatus::create(['name' => 'Fechou com o concorrente']);
    }

    public function down(): void
    {
        EventStatus::query()->delete();
    }
};
