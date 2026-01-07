<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_unidad');
            $table->string('color_unidad')->nullable();
            $table->string('logo_superior')->nullable();
            $table->string('logo_inferior')->nullable();
            $table->unsignedBigInteger('spa_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('unidades');
    }
};
