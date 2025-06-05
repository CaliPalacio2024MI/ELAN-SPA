<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla para configurar tiempos de código QR
        // Esta sería una tabla 1 a 1 con el hotel, ya que cada hotel tendrá su propia configuración
        Schema::create('gimnasio_config_qr_code', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_hotel')->constrained('spas')->unique()->onDelete('restrict');
            $table->integer('tiempo_renovacion_qr')->default(60); // Tiempo en minutos
            $table->integer('tiempo_validez_qr')->default(120); // Tiempo en minutos
            $table->timestamps();
        });

        // Tabla para códigos QR de acceso
        // tiempo_renovación_qr lo usarán los controladores para cambiar la página de QR, actualizarla con el nuevo token
        // tiempo_validez_qr lo usarán los controladores para validar si el QR sigue vigente
        // fecha_expiración siempre será Fecha de Creación + tiempo_validez_qr (puede ser calculado, pero lo guardamos para evitar cálculos innecesarios al validar el QR y para que el QR no se vuelva inválido si el servidor se reinicia u otros problemas)
        Schema::create('gimnasio_qrcodes', function (Blueprint $table) {
            $table->id();
            $table->string('token', 255)->unique();
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict');
            $table->enum('contexto', ['interno', 'externo'])->default('externo'); // Si es interno, el token hasta el final del día
            $table->datetime('fecha_expiracion'); // Fecha y hora de expiración del QR
            $table->boolean('activo')->default(true); // Para poder desactivar tokens sin eliminarlos
            $table->timestamps(); // Fecha de creación

            $table->index(['fk_id_hotel', 'contexto', 'activo']);
            $table->index('fecha_expiracion');
        });

        // Adultos
        Schema::create('gimnasio_registros_adultos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict');
            $table->enum('origen_registro', ['interno', 'externo'])->default('externo'); // Para saber de dónde vino
            $table->string('nombre_huesped', 100);
            $table->text('firma_huesped');
            $table->timestamps();
        });

        // Menores
        Schema::create('gimnasio_registros_menores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict');
            $table->enum('origen_registro', ['interno', 'externo'])->default('externo'); // Para saber de dónde vino
            $table->string('nombre_menor', 100);
            $table->integer('edad'); // 15-17
            $table->string('nombre_tutor', 100);
            $table->string('telefono_tutor', 20);
            $table->text('firma_tutor');
            $table->string('nombre_anfitrion', 100);
            $table->text('firma_anfitrion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gimnasio_config_qr_code');
        Schema::dropIfExists('gimnasio_registros_adultos');
        Schema::dropIfExists('gimnasio_registros_menores');
        Schema::dropIfExists('gimnasio_qrcodes');
    }
};
