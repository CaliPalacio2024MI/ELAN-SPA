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
        Schema::create('boutique_config_ventas_clasificacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 15);
            $table->integer('minimo_ventas');
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict');
            $table->timestamps();
            $table->unique(['nombre', 'fk_id_hotel'], 'unique_config_ventas_clasificacion_hotel');
        });

        Schema::create('boutique_articulos_familias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50); // Corporal, Facial, Cabello, Amenidad
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict');
            $table->timestamps();
            $table->unique(['nombre', 'fk_id_hotel'], 'unique_familia_hotel');
        });

        Schema::create('boutique_articulos', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_auxiliar');
            $table->string('nombre_articulo', 20);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio_publico_unidad', 10, 2)->nullable();
            $table->foreignId('fk_id_familia')->constrained('boutique_articulos_familias')->onDelete('restrict');
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['numero_auxiliar', 'fk_id_hotel'], 'unique_numero_auxiliar_hotel');
        });

        Schema::create('boutique_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_articulo')->constrained('boutique_articulos')->onDelete('restrict');
            $table->enum('tipo_compra', ['normal', 'directa'])->default('normal'); // Tipo de compra
            $table->string('folio_orden_compra', 50)->nullable(); // Solo se usa si es compra normal
            $table->string('folio_factura', 50); // Siempre se usa
            $table->decimal('costo_proveedor_unidad', 10, 2);
            $table->integer('cantidad_recibida');
            $table->date('fecha_caducidad')->nullable();
            $table->timestamps();
        });

        Schema::create('boutique_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_compra')->constrained('boutique_compras')->onDelete('restrict');
            $table->integer('cantidad_actual');
            $table->timestamps();
        });

        Schema::create('boutique_compras_eliminadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_compra')->constrained('boutique_compras')->onDelete('restrict');
            $table->string('motivo', 255)->default('Eliminación manual');
            $table->integer('cantidad_eliminada');
            $table->string('usuario_elimino', 20)->nullable(); // Para rastrear quién lo eliminó
            $table->timestamps();
        });

        Schema::create('boutique_formas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });

        Schema::create('boutique_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_hotel')->constrained('spas')->onDelete('restrict'); // 1->Palacio 2->Pierre 3->Princess
            $table->string('folio_venta', 20)->nullable(); // Nuevo: folio alfanumérico manual
            $table->foreignId('fk_id_forma_pago')->constrained('boutique_formas_pago')->onDelete('restrict'); // Nuevo: forma de pago (descriptivo)
            $table->string('referencia_pago', 100)->nullable(); // Nuevo: para guardar el número de referencia (habitación, tarjeta, etc.)
            $table->date('fecha_venta'); // esto es cuando se hizo la venta en la vida real (según algún ticket o comprobante)
            $table->time('hora_venta');
            $table->timestamps(); // esto es cuando se hizo la venta en el sistema
        });

        Schema::create('boutique_ventas_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_id_folio')->constrained('boutique_ventas')->onDelete('restrict');
            $table->foreignId('fk_id_compra')->constrained('boutique_compras')->onDelete('restrict');
            $table->foreignId('fk_id_anfitrion')->constrained('anfitriones')->onDelete('restrict'); // empleado que hizo la venta, una masajista no puede vender como tal, pero puede referir al cliente a la boutique para comprar el producto y entonces la venta cuenta como que ella la hizo, en este caso (anfitriones) se eligen por artículos, no por grupo de artículos
            $table->integer('cantidad');
            $table->decimal('descuento', 5, 2);
            $table->decimal('subtotal', 10, 2); // ya con descuento aplicado
            $table->string('observaciones', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boutique_config_ventas_clasificacion');
        Schema::dropIfExists('boutique_articulos_familias');
        Schema::dropIfExists('boutique_articulos');
        Schema::dropIfExists('boutique_compras');
        Schema::dropIfExists('boutique_inventario');
        Schema::dropIfExists('boutique_compras_eliminadas');
        Schema::dropIfExists('boutique_formas_pago');
        Schema::dropIfExists('boutique_ventas');
        Schema::dropIfExists('boutique_ventas_detalles');
    }
};
