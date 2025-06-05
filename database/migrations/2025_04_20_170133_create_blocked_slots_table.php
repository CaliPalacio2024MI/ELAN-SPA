<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spa_id');
            $table->unsignedBigInteger('anfitrion_id');
            $table->date('fecha');
            $table->time('hora');
            $table->integer('duracion')->default(10);
            $table->string('motivo')->nullable();
            $table->timestamps();
        
            $table->foreign('spa_id')->references('id')->on('spas')->onDelete('cascade');
            $table->foreign('anfitrion_id')->references('id')->on('anfitriones')->onDelete('cascade');
        });
        
    }


    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};
