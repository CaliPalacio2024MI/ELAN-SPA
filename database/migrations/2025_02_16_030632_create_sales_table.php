<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('grupo_reserva_id')->nullable()->constrained('grupo_reservas')->onDelete('set null');
            $table->foreignId('cliente_id')->constrained('clients')->onDelete('cascade'); // quien paga
            $table->foreignId('spa_id')->constrained('spas')->onDelete('cascade');
            $table->foreignId('reservacion_id')->nullable()->constrained('reservations');
            
            $table->enum('forma_pago', ['efectivo', 'tarjeta_debito', 'tarjeta_credito', 'habitacion', 'recepcion', 'transferencia', 'otro']);
            $table->string('referencia_pago')->nullable(); // voucher, habitación, folio, etc.
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('impuestos', 10, 2);
            $table->decimal('propina', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->boolean('cobrado')->default(true); // siempre se marca como cobrado al registrar

            $table->timestamps();
        });

    }

    public function down() {
        Schema::dropIfExists('sales');
    }
};
