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
        Schema::table('anfitrion_operativo', function (Blueprint $table) {
            // Cambiar el campo departamento de enum a string para permitir valores personalizados
            $table->string('departamento')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anfitrion_operativo', function (Blueprint $table) {
            // Revertir a enum si es necesario
            $table->enum('departamento', ['spa', 'gym', 'valet', 'salon de belleza', 'global'])->default('spa')->change();
        });
    }
};
