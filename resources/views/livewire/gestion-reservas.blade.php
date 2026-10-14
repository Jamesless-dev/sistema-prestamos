<div class="max-w-6xl mx-auto p-6">

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Gestión de Reservas</h2>
        <p class="text-gray-600 mt-2">Aprueba o rechaza las solicitudes de los estudiantes</p>
    </div>

    @if($mensajeExito)
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
            <p class="text-green-700 font-medium">{{ $mensajeExito }}</p>
        </div>
    @endif

    @if($mensajeError)
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
            <p class="text-red-700 font-medium">{{ $mensajeError }}</p>
        </div>
    @endif

    <h3 class="text-2xl font-bold text-gray-800 mb-4">Solicitudes Pendientes</h3>
    @if($pendientes->isEmpty())
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center text-gray-500 mb-10">
            No hay solicitudes pendientes.
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-10">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-4">Equipo</th>
                            <th class="px-6 py-4">Estudiante</th>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Horario</th>
                            <th class="px-6 py-4">Motivo</th>
                            <th class="px-6 py-4 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($pendientes as $reserva)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900">{{ $reserva->equipo->codigo }}</span><br>
                                    <span class="text-sm text-gray-600">{{ $reserva->equipo->nombre }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $reserva->estudiante->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $reserva->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $reserva->fecha_inicio->format('H:i') }} - {{ $reserva->fecha_fin->format('H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ $reserva->observaciones ?: 'Sin motivo' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="aprobar({{ $reserva->id }})"
                                            class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2 px-4 rounded-lg">
                                            Aprobar
                                        </button>
                                        <button wire:click="rechazar({{ $reserva->id }})"
                                            wire:confirm="¿Rechazar esta solicitud?"
                                            class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2 px-4 rounded-lg">
                                            Rechazar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <h3 class="text-2xl font-bold text-gray-800 mb-4">Reservas Aprobadas</h3>
    @if($aprobadas->isEmpty())
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center text-gray-500 mb-10">
            No hay reservas aprobadas.
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-10">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-4">Equipo</th>
                            <th class="px-6 py-4">Estudiante</th>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Horario</th>
                            <th class="px-6 py-4 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($aprobadas as $reserva)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-900">{{ $reserva->equipo->codigo }}</span><br>
                                    <span class="text-sm text-gray-600">{{ $reserva->equipo->nombre }}</span>
                                    <span class="ml-1 text-xs text-gray-500">({{ ucfirst($reserva->equipo->estado) }})</span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $reserva->estudiante->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $reserva->fecha_inicio->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $reserva->fecha_inicio->format('H:i') }} - {{ $reserva->fecha_fin->format('H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="entregar({{ $reserva->id }})"
                                            wire:confirm="¿El estudiante está retirando el equipo? Se registrará como préstamo."
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-lg">
                                            Entregar
                                        </button>
                                        <button wire:click="cancelar({{ $reserva->id }})"
                                            wire:confirm="¿Cancelar esta reserva y liberar el equipo?"
                                            class="text-red-600 underline text-sm">
                                            Cancelar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($historial->isNotEmpty())
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Últimas reservas cerradas</h3>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-600">
                        @foreach($historial as $reserva)
                            <tr>
                                <td class="px-6 py-3">{{ $reserva->equipo->codigo }} - {{ $reserva->equipo->nombre }}</td>
                                <td class="px-6 py-3">{{ $reserva->estudiante->name }}</td>
                                <td class="px-6 py-3">{{ $reserva->fecha_inicio->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3">{{ ucfirst($reserva->estado) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>