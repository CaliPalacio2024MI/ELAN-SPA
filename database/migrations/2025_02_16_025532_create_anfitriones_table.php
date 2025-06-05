<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones: crea las tablas relacionadas a anfitriones y horarios.
     */
    public function up()
    {
        // Tabla principal de anfitriones
        Schema::create('anfitriones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained('spas')->onDelete('cascade');
            $table->string('RFC')->unique();
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->string('nombre_usuario');
            $table->string('password');
            $table->enum('rol', ['master', 'administrador', 'recepcionista', 'anfitrion'])->default('anfitrion');
            $table->json('accesos')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Tabla para datos operativos de anfitriones
        Schema::create('anfitrion_operativo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anfitrion_id')->constrained('anfitriones')->onDelete('cascade')->unique();
            $table->enum('departamento', ['spa', 'gym', 'valet', 'salon de belleza', 'global'])->default('spa');
            $table->json('clases_actividad')->nullable();
            $table->timestamps();
        });

        // Tabla de horarios asociados a anfitriones
        Schema::create('horarios_anfitrion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anfitrion_id')->constrained('anfitriones')->onDelete('cascade');
            $table->json('horarios');
            $table->timestamps();
        });
    }

    /**
     * Revertir las migraciones: eliminar tablas creadas.
     */
    public function down()
    {
        Schema::dropIfExists('anfitrion_operativo');
        Schema::dropIfExists('horarios_anfitrion');
        Schema::dropIfExists('anfitriones');
    }
};
