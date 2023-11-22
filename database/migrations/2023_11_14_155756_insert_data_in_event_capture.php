<?php

use App\Models\EventCapture;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        EventCapture::create(['name' => 'Passiva']);
        EventCapture::create(['name' => 'Ativa']);
        
    }

    public function down(): void
    {
        EventCapture::query()->delete();   
    }
};
