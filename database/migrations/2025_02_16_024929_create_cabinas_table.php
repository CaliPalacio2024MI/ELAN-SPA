<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cabinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained('spas')->onDelete('cascade');
            $table->string('nombre'); 
            $table->string('clase')->default('individual'); 
            $table->json('clases_actividad')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('cabinas');
    }
};
