<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BoutiqueFormasPago extends Model
{
    use HasFactory;

    protected $table = 'boutique_formas_pago';
    protected $fillable = [
        'nombre',
    ];

    // Relación inversa: una forma de pago tiene muchas ventas
    public function ventas()
    {
        return $this->hasMany(BoutiqueVenta::class, 'fk_id_forma_pago');
    }
}