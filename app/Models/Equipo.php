<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    public const DISPONIBLE = 'disponible';

    public const PRESTADO = 'prestado';

    public const ESTADOS = [self::DISPONIBLE, self::PRESTADO];

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

    public function estaDisponible()
    {
        return $this->estado === self::DISPONIBLE;
    }
}
