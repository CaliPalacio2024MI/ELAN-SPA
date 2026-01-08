<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use HasFactory;

    protected $table = 'unidades';

    protected $fillable = [
        'nombre_unidad',
        'color_unidad',
        'logo_superior',
        'logo_unidad',
        'logo_inferior',
        'spa_id',
        // Asegúrate de que todos los campos de color del tema también estén aquí
        'color_sidebar_bg',
        'color_sidebar_hover_bg',
        'color_icon',
        'color_text',
        'color_submenu_bg',
        'color_submenu_link_bg',
        'color_submenu_link_hover_bg',
        // Añadimos los nuevos campos para que se puedan guardar
        'color_logout_text_color',
        'color_logout_icon_color',
    ];

    /**
     * Obtiene el registro de Spa al que pertenece esta Unidad.
     */
    public function spa()
    {
        return $this->belongsTo(Spa::class, 'spa_id');
    }
}
