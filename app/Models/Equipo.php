<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    public const DISPONIBLE = 'disponible';

    public const PRESTADO = 'prestado';

    public const RESERVADO = 'reservado';

    public const ESTADOS = [self::DISPONIBLE, self::PRESTADO, self::RESERVADO];

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria',
        'estado',
    ];

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    public function sincronizarEstadoReserva(): void
    {
        $this->reservas()->where('estado', Reserva::APROBADA)
            ->where('fecha_fin', '<=', now())->update(['estado' => Reserva::CANCELADA]);

        $this->update(['estado' => $this->estadoSegunMovimientos()]);
    }

    public function estadoSegunMovimientos(): string
    {
        if ($this->prestamos()->whereNull('fecha_devolucion')->exists()) {
            return self::PRESTADO;
        }

        return $this->reservas()->where('estado', Reserva::APROBADA)
            ->where('fecha_fin', '>', now())->exists() ? self::RESERVADO : self::DISPONIBLE;
    }

    public function estaDisponible()
    {
        return $this->estado === self::DISPONIBLE;
    }
}
