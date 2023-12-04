<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nickname');
            $table->date('birth_date');
            $table->string('cpf');
            $table->string('rg');
            $table->string('email');
            $table->string('phone_1');
            $table->string('phone_2');
            $table->foreignId('sector_id')->constrained('sectors');
            $table->foreignId('office_id')->constrained('offices');
            $table->boolean('has_login')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('file_id')->nullable()->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};
