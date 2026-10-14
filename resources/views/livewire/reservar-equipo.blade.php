<div>
    <h1 class="text-2xl font-bold mb-4">Reservar Equipo</h1>

    {{-- Selector de equipo --}}
    <div class="mb-4">
        <label for="equipo" class="block text-sm font-medium text-gray-700">Seleccionar Equipo</label>
        <select id="equipo" wire:model="equipoSeleccionado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">-- Selecciona un equipo --</option>
            @foreach($equipos as $equipo)
                <option value="{{ $equipo->id }}">
                    {{ $equipo->categoria }} - {{ $equipo->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Navegación de mes --}}
    <div class="flex justify-between items-center mb-4">
        <button wire:click="mesAnterior" class="px-4 py-2 bg-gray-200 rounded">← Anterior</button>
        <h2 class="text-xl font-semibold">{{ $inicioMes->format('F Y') }}</h2>
        <button wire:click="mesSiguiente" class="px-4 py-2 bg-gray-200 rounded">Siguiente →</button>
    </div>

    {{-- Calendario --}}
    @if($equipoSeleccionado)
        <div class="grid grid-cols-7 gap-2 mb-6">
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia)
                <div class="text-center font-bold text-gray-600">{{ $dia }}</div>
            @endforeach

            @foreach($semanas as $semana)
                @foreach($semana as $dia)
                    <div class="border p-2 min-h-[80px] {{ $dia ? 'bg-white' : 'bg-gray-100' }}">
                        @if($dia)
                            <div class="font-bold">{{ $dia->format('d') }}</div>
                            @php
                                $fechaStr = $dia->format('Y-m-d');
                                $ocupados = $ocupadosPorDia->get($fechaStr, collect());
                            @endphp
                            @foreach($ocupados as $reserva)
                                <div class="text-xs bg-blue-100 rounded p-1 mt-1">
                                    {{ $reserva->estudiante->name ?? 'Estudiante' }}
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <p class="text-gray-500">Selecciona un equipo para ver el calendario.</p>
    @endif

    {{-- Mis reservas --}}
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-2">Mis Reservas</h3>
        @if($misReservas->isEmpty())
            <p class="text-gray-500">No tienes reservas activas.</p>
        @else
            <div class="space-y-2">
                @foreach($misReservas as $reserva)
                    <div class="border rounded p-3 flex justify-between items-center">
                        <div>
                            <strong>{{ $reserva->equipo->nombre ?? 'Equipo' }}</strong>
                            <p class="text-sm text-gray-600">
                                {{ $reserva->fecha_inicio->format('d/m/Y H:i') }} - 
                                {{ $reserva->fecha_fin->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded text-sm 
                            {{ $reserva->estado === 'aprobada' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $reserva->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $reserva->estado === 'rechazada' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $reserva->estado === 'cancelada' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $reserva->estado === 'finalizada' ? 'bg-blue-100 text-blue-800' : '' }}
                        ">
                            {{ ucfirst($reserva->estado) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>