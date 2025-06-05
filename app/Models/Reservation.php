<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'spa_id',
        'cliente_id',
        'experiencia_id',
        'fecha',
        'hora',
        'cabina_id',
        'anfitrion_id',
        'observaciones',
        'check_in',
        'check_out',
        'locker',
        'grupo_reserva_id',
        'es_principal',
        'estado'
    ];

    public function spa()
    {
        return $this->belongsTo(Spa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class);
    }

    public function experiencia()
    {
        return $this->belongsTo(Experience::class);
    }

    public function anfitrion()
    {
        return $this->belongsTo(Anfitrion::class);
    }

    public function cabina()
    {
        return $this->belongsTo(Cabina::class);
    }

    public function grupoReserva()
    {
        return $this->belongsTo(GrupoReserva::class, 'grupo_reserva_id');
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'reservacion_id');
    }

    public function evaluationForm()
    {
        return $this->hasOne(EvaluationForm::class, 'reservation_id');
    }

    
}
