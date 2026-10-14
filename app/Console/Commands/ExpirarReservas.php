<?php

namespace App\Console\Commands;

use App\Models\Equipo;
use App\Models\Reserva;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirarReservas extends Command
{
    protected $signature = 'reservas:expirar';

    protected $description = 'Cierra reservas vencidas y recalcula el estado de sus equipos';

    public function handle(): int
    {
        $ids = Reserva::whereIn('estado', [Reserva::PENDIENTE, Reserva::APROBADA])
            ->where('fecha_fin', '<=', now())->distinct()->pluck('equipo_id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id) {
                $equipo = Equipo::lockForUpdate()->findOrFail($id);
                $equipo->reservas()->whereIn('estado', [Reserva::PENDIENTE, Reserva::APROBADA])
                    ->where('fecha_fin', '<=', now())->update(['estado' => Reserva::CANCELADA]);
                $equipo->sincronizarEstadoReserva();
            }, 3);
        }

        $this->info('Reservas vencidas procesadas.');

        return self::SUCCESS;
    }
}
