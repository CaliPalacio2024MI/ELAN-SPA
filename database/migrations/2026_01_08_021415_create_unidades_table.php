<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_unidad')->unique();
            $table->string('color_unidad', 7);

            // Logos
            $table->string('logo_superior')->nullable();
            $table->string('logo_unidad')->nullable();
            $table->string('logo_inferior')->nullable();

            // Relación con la tabla 'spas'
            $table->foreignId('spa_id')->constrained('spas')->onDelete('cascade');

            // Columnas para el tema del menú dinámico
            $table->string('color_sidebar_bg', 7)->nullable();
            $table->string('color_sidebar_hover_bg', 7)->nullable();
            $table->string('color_icon', 7)->nullable();
            $table->string('color_text', 7)->nullable();
            $table->string('color_submenu_bg', 7)->nullable();
            $table->string('color_submenu_link_bg', 7)->nullable();
            $table->string('color_submenu_link_hover_bg', 7)->nullable();
            $table->string('color_logout_text_color', 7)->nullable();
            $table->string('color_logout_icon_color', 7)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unidades');
    }
};
