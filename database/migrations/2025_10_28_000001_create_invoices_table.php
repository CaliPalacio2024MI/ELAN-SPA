<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('cascade');
            $table->foreignId('reservacion_id')->nullable()->constrained('reservations')->onDelete('set null');
            $table->foreignId('cliente_id')->nullable()->constrained('clients')->onDelete('set null');

            $table->enum('tipo_persona', ['fisica', 'moral'])->nullable();
            $table->string('razon_social')->nullable();
            $table->string('rfc')->nullable();
            $table->string('direccion_fiscal')->nullable();
            $table->string('correo_factura')->nullable();

            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('invoices');
    }
};
