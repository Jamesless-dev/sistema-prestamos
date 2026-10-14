<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    public const PENDIENTE = 'pendiente';
    public const APROBADA = 'aprobada';
    public const RECHAZADA = 'rechazada';
    public const CANCELADA = 'cancelada';
    public const FINALIZADA = 'finalizada';

    protected $fillable = [
        'equipo_id', 'estudiante_id', 'fecha_inicio', 'fecha_fin', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public static function hayConflicto($equipoId, $inicio, $fin, $ignorarId = null): bool
    {
        return static::where('equipo_id', $equipoId)
            ->where('estado', self::APROBADA)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where('fecha_inicio', '<', $fin)
            ->where('fecha_fin', '>', $inicio)
            ->exists();
    }
}