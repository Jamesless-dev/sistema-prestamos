<div>
    <h1 class="text-2xl font-bold mb-6">Gestión de Reservas</h1>

    {{-- Mensajes de feedback --}}
    @if($mensajeExito)
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ $mensajeExito }}
        </div>
    @endif

    @if($mensajeError)
        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $mensajeError }}
        </div>
    @endif

    {{-- Tabs de navegación --}}
    <div class="mb-6 border-b">
        <nav class="flex space-x-4">
            <button wire:click="$set('tab', 'pendientes')"
                class="px-4 py-2 {{ $tab === 'pendientes' ? 'border-b-2 border-blue-500 text-blue-600 font-semibold' : 'text-gray-500' }}">
                Pendientes ({{ $pendientes->count() }})
            </button>
            <button wire:click="$set('tab', 'aprobadas')"
                class="px-4 py-2 {{ $tab === 'aprobadas' ? 'border-b-2 border-green-500 text-green-600 font-semibold' : 'text-gray-500' }}">
                Aprobadas ({{ $aprobadas->count() }})
            </button>
            <button wire:click="$set('tab', 'historial')"
                class="px-4 py-2 {{ $tab === 'historial' ? 'border-b-2 border-gray-500 text-gray-600 font-semibold' : 'text-gray-500' }}">
                Historial
            </button>
        </nav>
    </div>

    {{-- Tab: Pendientes --}}
    @if($tab === 'pendientes')
        <div class="space-y-3">
            @if($pendientes->isEmpty())
                <p class="text-gray-500">No hay reservas pendientes.</p>
            @else
                @foreach($pendientes as $reserva)
                    <div class="border rounded-lg p-4 flex justify-between items-center bg-yellow-50">
                        <div>
                            <p class="font-semibold">
                                {{ $reserva->estudiante->name ?? 'Estudiante' }}
                                <span class="text-gray-500 font-normal">— {{ $reserva->equipo->nombre ?? 'Equipo' }}</span>
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ $reserva->fecha_inicio->format('d/m/Y H:i') }} →
                                {{ $reserva->fecha_fin->format('d/m/Y H:i') }}
                            </p>
                            @if($reserva->observaciones)
                                <p class="text-sm text-gray-500 mt-1">
                                    <em>{{ $reserva->observaciones }}</em>
                                </p>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <button wire:click="aprobar({{ $reserva->id }})"
                                class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                Aprobar
                            </button>
                            <button wire:click="rechazar({{ $reserva->id }})"
                                class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                Rechazar
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- Tab: Aprobadas --}}
    @if($tab === 'aprobadas')
        <div class="space-y-3">
            @if($aprobadas->isEmpty())
                <p class="text-gray-500">No hay reservas aprobadas.</p>
            @else
                @foreach($aprobadas as $reserva)
                    <div class="border rounded-lg p-4 flex justify-between items-center bg-green-50">
                        <div>
                            <p class="font-semibold">
                                {{ $reserva->estudiante->name ?? 'Estudiante' }}
                                <span class="text-gray-500 font-normal">— {{ $reserva->equipo->nombre ?? 'Equipo' }}</span>
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ $reserva->fecha_inicio->format('d/m/Y H:i') }} →
                                {{ $reserva->fecha_fin->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button wire:click="entregar({{ $reserva->id }})"
                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Entregar
                            </button>
                            <button wire:click="cancelar({{ $reserva->id }})"
                                class="px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Cancelar
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- Tab: Historial --}}
    @if($tab === 'historial')
        <div class="space-y-3">
            @if($historial->isEmpty())
                <p class="text-gray-500">No hay historial de reservas.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estudiante</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fin</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($historial as $reserva)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $reserva->estudiante->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $reserva->equipo->nombre ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $reserva->fecha_inicio->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $reserva->fecha_fin->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 rounded text-xs
                                            {{ $reserva->estado === 'aprobada' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $reserva->estado === 'rechazada' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $reserva->estado === 'cancelada' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $reserva->estado === 'finalizada' ? 'bg-blue-100 text-blue-800' : '' }}
                                        ">
                                            {{ ucfirst($reserva->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>