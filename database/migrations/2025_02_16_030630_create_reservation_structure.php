<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Grupo de reservas
        Schema::create('grupo_reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clients')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Reservaciones
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spa_id')->constrained('spas')->onDelete('cascade');
            $table->foreignId('experiencia_id')->constrained('experiences')->onDelete('cascade'); 
            $table->foreignId('cabina_id')->nullable()->constrained('cabinas')->onDelete('set null'); 
            $table->foreignId('anfitrion_id')->constrained('anfitriones')->onDelete('cascade'); 
            $table->foreignId('cliente_id')->constrained('clients')->onDelete('cascade'); 
            $table->foreignId('grupo_reserva_id')->nullable()->constrained('grupo_reservas')->onDelete('set null');
            $table->boolean('es_principal')->default(true);  
            $table->date('fecha');
            $table->time('hora');             
            $table->text('observaciones')->nullable();
            $table->boolean('check_in')->default(false);
            $table->boolean('check_out')->default(false);                     
            $table->string('locker')->nullable();
            $table->enum('estado', ['activa', 'cancelada'])->default('activa');
            $table->timestamps();
        });

        // 3. Formularios de evaluación médica
        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clients')->onDelete('set null');

            $table->json('preguntas_respuestas');
            $table->text('observaciones')->nullable();

            $table->string('firma_paciente_url')->nullable();
            $table->string('firma_tutor_url')->nullable();
            $table->string('firma_doctor_url')->nullable();
            $table->string('firma_testigo1_url')->nullable();
            $table->string('firma_testigo2_url')->nullable();

            $table->string('firma_padre_url')->nullable();
            $table->string('firma_terapeuta_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_forms');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('grupo_reservas');
    }
};
