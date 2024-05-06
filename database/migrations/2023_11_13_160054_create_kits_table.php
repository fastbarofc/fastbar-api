<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->nullable()->cascadeOnDelete()->constrained();
            $table->string('slug');
            $table->string('name');
            $table->boolean('themed_table');
            $table->boolean('optional');
            $table->decimal('value', 9, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kits');
    }
};
