<div>
    <h1 class="text-2xl font-bold mb-6">Reservar Equipo</h1>

    {{-- Mensajes --}}
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

    {{-- Selector de equipo --}}
    <div class="mb-4">
        <label for="equipo_id" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Equipo</label>
        <select id="equipo_id" wire:model.live="equipo_id" class="w-full rounded-md border-gray-300 shadow-sm">
            <option value="">-- Selecciona un equipo --</option>
            @foreach($equipos as $equipo)
                <option value="{{ $equipo->id }}">
                    {{ $equipo->categoria }} - {{ $equipo->nombre }}
                </option>
            @endforeach
        </select>
        @error('equipo_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Navegación de mes --}}
    <div class="flex justify-between items-center mb-4">
        <button wire:click="mesAnterior" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">← Anterior</button>
        <h2 class="text-xl font-semibold">{{ $inicioMes->format('F Y') }}</h2>
        <button wire:click="mesSiguiente" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Siguiente →</button>
    </div>

    {{-- Calendario --}}
    @if($equipoSeleccionado)
        <div class="grid grid-cols-7 gap-2 mb-6">
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaNombre)
                <div class="text-center font-bold text-gray-600 py-2">{{ $diaNombre }}</div>
            @endforeach

            @foreach($semanas as $semana)
                @foreach($semana as $dia)
                    @php
                        $esDelMes = $dia->month === $inicioMes->month;
                        $fechaStr = $dia->format('Y-m-d');
                        $esPasado = $dia->lt(today());
                        $ocupados = $ocupadosPorDia->get($fechaStr, collect());
                        $estaSeleccionado = $fecha === $fechaStr;
                    @endphp
                    <div class="border p-2 min-h-[80px] {{ $esDelMes ? 'bg-white' : 'bg-gray-100' }} {{ $estaSeleccionado ? 'ring-2 ring-blue-500' : '' }}">
                        @if($esDelMes)
                            <button
                                wire:click="seleccionarDia('{{ $fechaStr }}')"
                                class="w-full text-left {{ $esPasado ? 'text-gray-400 cursor-not-allowed' : 'hover:bg-blue-50' }}"
                                {{ $esPasado ? 'disabled' : '' }}
                            >
                                <span class="font-bold">{{ $dia->format('d') }}</span>
                            </button>
                            @foreach($ocupados as $reserva)
                                <div class="text-xs bg-yellow-100 text-yellow-800 rounded p-1 mt-1">
                                    {{ $reserva->fecha_inicio->format('H:i') }}-{{ $reserva->fecha_fin->format('H:i') }}
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <p class="text-gray-500 mb-6">Selecciona un equipo para ver el calendario.</p>
    @endif

    {{-- Formulario de reserva --}}
    @if($fecha)
        <div class="border rounded-lg p-4 mb-6 bg-blue-50">
            <h3 class="font-semibold mb-3">Reservar para el {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h3>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio</label>
                    <input type="time" wire:model="hora_inicio" class="w-full rounded-md border-gray-300 shadow-sm">
                    @error('hora_inicio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin</label>
                    <input type="time" wire:model="hora_fin" class="w-full rounded-md border-gray-300 shadow-sm">
                    @error('hora_fin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones (opcional)</label>
                <textarea wire:model="observaciones" rows="2" class="w-full rounded-md border-gray-300 shadow-sm" maxlength="255"></textarea>
                @error('observaciones') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <button wire:click="reservar" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Confirmar Reserva
            </button>
        </div>
    @endif

    {{-- Mis reservas --}}
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-3">Mis Reservas</h3>
        @if($misReservas->isEmpty())
            <p class="text-gray-500">No tienes reservas activas.</p>
        @else
            <div class="space-y-2">
                @foreach($misReservas as $reserva)
                    <div class="border rounded-lg p-3 flex justify-between items-center
                        {{ $reserva->estado === 'pendiente' ? 'bg-yellow-50' : '' }}
                        {{ $reserva->estado === 'aprobada' ? 'bg-green-50' : '' }}
                        {{ $reserva->estado === 'cancelada' ? 'bg-gray-50' : '' }}
                    ">
                        <div>
                            <p class="font-semibold">
                                {{ $reserva->equipo->nombre ?? 'Equipo' }}
                                <span class="text-gray-500 font-normal text-sm">
                                    — {{ $reserva->fecha_inicio->format('d/m/Y H:i') }} → {{ $reserva->fecha_fin->format('d/m/Y H:i') }}
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 rounded text-xs
                                {{ $reserva->estado === 'aprobada' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $reserva->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $reserva->estado === 'rechazada' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $reserva->estado === 'cancelada' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $reserva->estado === 'finalizada' ? 'bg-blue-100 text-blue-800' : '' }}
                            ">
                                {{ ucfirst($reserva->estado) }}
                            </span>
                            @if(in_array($reserva->estado, ['pendiente', 'aprobada']))
                                <button wire:click="cancelar({{ $reserva->id }})"
                                    wire:confirm="¿Seguro que deseas cancelar esta reserva?"
                                    class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                    Cancelar
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>