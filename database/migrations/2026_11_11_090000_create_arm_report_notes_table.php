<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Textos das páginas do documento ARM (como na MAV): um texto por mês e por página. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arm_report_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('section', 40);
            $table->text('body')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['year', 'month', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arm_report_notes');
    }
};
