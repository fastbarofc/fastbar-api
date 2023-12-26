<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('how_met')->nullable();
            $table->string('type');
            $table->string('email');
            $table->string('phone_1');
            $table->string('phone_2')->nullable();
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('company_name')->nullable();
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();
            $table->string('fantasy_name')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('ie')->nullable();
            $table->string('im')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
