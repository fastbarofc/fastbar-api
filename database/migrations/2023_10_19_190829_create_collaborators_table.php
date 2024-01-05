<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();
            $table->string('email');
            $table->string('phone_1');
            $table->string('phone_2')->nullable();
            $table->foreignId('sector_id')->constrained();
            $table->foreignId('office_id')->constrained();
            $table->boolean('has_login')->default(false);
            $table->boolean('active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('file_id')->nullable()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};
