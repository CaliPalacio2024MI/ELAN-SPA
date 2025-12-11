<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('departamentos', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('spa_id');
        $table->string('nombre');
        $table->boolean('activo')->default(true);
        $table->timestamps();

        $table->foreign('spa_id')->references('id')->on('spas')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};
