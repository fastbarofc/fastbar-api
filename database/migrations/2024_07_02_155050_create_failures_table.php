<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailuresTable extends Migration
{
    public function up()
    {
        Schema::create('failures', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('percentage', 5, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('failures');
    }
}
