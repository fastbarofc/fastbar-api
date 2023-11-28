<?php

use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Unit::create(['description' => 'Caixa']);
        Unit::create(['description' => 'Garrafa']);
        Unit::create(['description' => 'Litro']);
        Unit::create(['description' => 'Pacote']);
        Unit::create(['description' => 'Quilo']);
        Unit::create(['description' => 'Unidade']);   
    }

    public function down(): void
    {
        Unit::query()->delete(); 
    }
};
