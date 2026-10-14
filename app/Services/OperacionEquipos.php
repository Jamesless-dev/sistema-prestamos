<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Prestamo;
use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class OperacionEquipos
{
    public function prestar(int $equipoId, int $estudianteId, ?string $observaciones = null): Prestamo
    {
        return DB::transaction(function () use ($equipoId, $estudianteId, $observaciones) {
            $equipo = Equipo::lockForUpdate()->findOrFail($equipoId);
            $equipo->sincronizarEstadoReserva();
            $this->validarEstudiante($estudianteId);

            if (! $equipo->estaDisponible()) {
                throw new DomainException('El equipo ya no está disponible. Actualiza la selección.');
            }

            $prestamo = $equipo->prestamos()->create([
                'estudiante_id' => $estudianteId,
                'fecha_prestamo' => now(),
                'observaciones' => $observaciones,
            ]);
            $equipo->update(['estado' => Equipo::PRESTADO]);

            return $prestamo;
        }, 3);
    }

    public function devolver(int $prestamoId): void
    {
        $equipoId = Prestamo::findOrFail($prestamoId)->equipo_id;

        DB::transaction(function () use ($prestamoId, $equipoId) {
            $equipo = Equipo::lockForUpdate()->findOrFail($equipoId);
            $prestamo = Prestamo::lockForUpdate()->findOrFail($prestamoId);

            if (! $prestamo->estaActivo()) {
                throw new DomainException('Este préstamo ya tiene una devolución registrada.');
            }

            $prestamo->update(['fecha_devolucion' => now()]);
            $equipo->sincronizarEstadoReserva();
        }, 3);
    }

    public function solicitarReserva(int $equipoId, int $estudianteId, Carbon $inicio, Carbon $fin, ?string $observaciones): Reserva
    {
        return DB::transaction(function () use ($equipoId, $estudianteId, $inicio, $fin, $observaciones) {
            Equipo::lockForUpdate()->findOrFail($equipoId);
            $this->validarEstudiante($estudianteId);

            if ($inicio->lte(now()) || $fin->lte($inicio)) {
                throw new DomainException('La reserva debe tener un horario futuro y una hora de fin posterior al inicio.');
            }

            if (Reserva::hayConflicto($equipoId, $inicio, $fin)) {
                throw new DomainException('Ese equipo ya está reservado en ese horario.');
            }

            if (Reserva::where('equipo_id', $equipoId)->where('estudiante_id', $estudianteId)
                ->where('estado', Reserva::PENDIENTE)->where('fecha_inicio', $inicio)->where('fecha_fin', $fin)->exists()) {
                throw new DomainException('Ya tienes una solicitud pendiente para ese equipo y horario.');
            }

            return Reserva::create([
                'equipo_id' => $equipoId,
                'estudiante_id' => $estudianteId,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado' => Reserva::PENDIENTE,
                'observaciones' => $observaciones,
            ]);
        }, 3);
    }

    public function aprobarReserva(int $reservaId): void
    {
        $equipoId = Reserva::findOrFail($reservaId)->equipo_id;

        DB::transaction(function () use ($reservaId, $equipoId) {
            $equipo = Equipo::lockForUpdate()->findOrFail($equipoId);
            $reserva = Reserva::lockForUpdate()->findOrFail($reservaId);
            $this->validarEstudiante($reserva->estudiante_id);

            if ($reserva->estado !== Reserva::PENDIENTE) {
                throw new DomainException('Esta reserva ya fue procesada.');
            }
            if ($reserva->fecha_inicio->lte(now())) {
                throw new DomainException('No se puede aprobar: el horario de inicio ya pasó.');
            }
            if (Reserva::hayConflicto($equipoId, $reserva->fecha_inicio, $reserva->fecha_fin, $reservaId)) {
                throw new DomainException('El equipo tiene otra reserva aprobada en ese horario.');
            }

            $reserva->update(['estado' => Reserva::APROBADA]);
            $equipo->sincronizarEstadoReserva();
        }, 3);
    }

    public function rechazarReserva(int $reservaId): void
    {
        $this->cerrarReserva($reservaId, Reserva::RECHAZADA, [Reserva::PENDIENTE]);
    }

    public function cancelarReserva(int $reservaId, ?int $estudianteId = null): void
    {
        $this->cerrarReserva($reservaId, Reserva::CANCELADA, [Reserva::PENDIENTE, Reserva::APROBADA], $estudianteId);
    }

    private function cerrarReserva(int $reservaId, string $estado, array $permitidos, ?int $estudianteId = null): void
    {
        $reserva = Reserva::when($estudianteId !== null, fn ($query) => $query->where('estudiante_id', $estudianteId))->findOrFail($reservaId);

        DB::transaction(function () use ($reservaId, $reserva, $estado, $permitidos, $estudianteId) {
            $equipo = Equipo::lockForUpdate()->findOrFail($reserva->equipo_id);
            $actual = Reserva::lockForUpdate()
                ->when($estudianteId !== null, fn ($query) => $query->where('estudiante_id', $estudianteId))->findOrFail($reservaId);

            if (! in_array($actual->estado, $permitidos, true)) {
                throw new DomainException('La reserva ya fue procesada y no permite esta acción.');
            }

            $actual->update(['estado' => $estado]);
            $equipo->sincronizarEstadoReserva();
        }, 3);
    }

    public function entregarReserva(int $reservaId): Prestamo
    {
        $equipoId = Reserva::findOrFail($reservaId)->equipo_id;

        return DB::transaction(function () use ($reservaId, $equipoId) {
            $equipo = Equipo::lockForUpdate()->findOrFail($equipoId);
            $reserva = Reserva::lockForUpdate()->findOrFail($reservaId);
            $this->validarEstudiante($reserva->estudiante_id);

            if ($reserva->estado !== Reserva::APROBADA) {
                throw new DomainException('Solo se pueden entregar reservas aprobadas.');
            }
            if (now()->lt($reserva->fecha_inicio) || now()->gte($reserva->fecha_fin)) {
                throw new DomainException('La entrega debe realizarse dentro del horario reservado.');
            }
            if ($equipo->prestamos()->whereNull('fecha_devolucion')->exists()) {
                throw new DomainException('El equipo todavía está prestado a otra persona.');
            }

            $prestamo = $equipo->prestamos()->create([
                'estudiante_id' => $reserva->estudiante_id,
                'fecha_prestamo' => now(),
                'observaciones' => 'Entrega de la reserva #'.$reserva->id,
            ]);
            $reserva->update(['estado' => Reserva::FINALIZADA]);
            $equipo->update(['estado' => Equipo::PRESTADO]);

            return $prestamo;
        }, 3);
    }

    private function validarEstudiante(int $estudianteId): void
    {
        $estudiante = User::lockForUpdate()->findOrFail($estudianteId);

        if ($estudiante->role !== User::ROLE_ESTUDIANTE) {
            throw new DomainException('El destinatario debe ser un estudiante.');
        }
    }
}
