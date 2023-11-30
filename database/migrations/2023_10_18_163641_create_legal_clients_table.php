<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('fantasy_name')->nullable();
            $table->string('cnpj')->nullable()->unique();
            $table->string('ie')->nullable();
            $table->string('im')->nullable();
            $table->foreignId('client_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_clients');
    }
};
