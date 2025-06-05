<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('correo', 150)->nullable()->unique(); 
            $table->string('telefono', 20);
            $table->enum('tipo_visita', [
                'palacio mundo imperial',
                'princess mundo imperial',
                'pierre mundo imperial',
                'condominio',
                'locales'
            ]);
            $table->timestamps();
        });        
    }

    public function down() {
        Schema::dropIfExists('clients');
    }
};
