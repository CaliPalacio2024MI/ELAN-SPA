<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('anfitriones', function (Blueprint $table) {
            // Agregamos la columna si no existe
            if (!Schema::hasColumn('anfitriones', 'porcentaje_servicio')) {
                $table->decimal('porcentaje_servicio', 5, 2)->nullable()->default(15.00)->after('activo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anfitriones', function (Blueprint $table) {
            $table->dropColumn('porcentaje_servicio');
        });
    }
};