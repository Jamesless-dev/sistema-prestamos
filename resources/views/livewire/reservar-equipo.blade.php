<div class="max-w-5xl mx-auto p-6">

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Reservar un Equipo</h2>
        <p class="text-gray-600 mt-2">Elige un equipo, mira su disponibilidad en el calendario y solicita tu reserva</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-lg shadow-md p-6">
            <label for="equipo" class="block text-sm font-semibold text-gray-700 mb-2">
                Equipo <span class="text-red-500">*</span>
            </label>
            <select id="equipo" wire:model.live="equipo_id"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white mb-1">
                <option value="">-- Selecciona un equipo --</option>
                @foreach($equipos as $equipo)
                    <option value="{{ $equipo->id }}">
                        {{ $equipo->codigo }} - {{ $equipo->nombre }} ({{ $equipo->categoria }})
                    </option>
                @endforeach
            </select>
            @error('equipo_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if($equipoSeleccionado)
                <div class="flex items-center justify-between mt-6 mb-3">
                    <button type="button" wire:click="mesAnterior"
                        class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700" aria-label="Mes anterior">&larr;</button>
                    <h3 class="font-semibold text-gray-800">
                        {{ ucfirst($inicioMes->locale('es')->translatedFormat('F Y')) }}
                    </h3>
                    <button type="button" wire:click="mesSiguiente"
                        class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700" aria-label="Mes siguiente">&rarr;</button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-500 mb-1">
                    @foreach(['Lu','Ma','Mi','Ju','Vi','Sá','Do'] as $d)
                        <div>{{ $d }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-1">
                    @foreach($semanas as $semana)
                        @foreach($semana as $dia)
                            @php
                                $clave = $dia->format('Y-m-d');
                                $delMes = $dia->month === $inicioMes->month;
                                $pasado = $dia->lt(today());
                                $reservasDia = $ocupadosPorDia->get($clave, collect());
                                $tieneAprobada = $reservasDia->contains('estado', 'aprobada');
                                $tienePendiente = $reservasDia->contains('estado', 'pendiente');

                                $clases = 'h-10 rounded text-sm flex items-center justify-center transition ';
                                if (! $delMes) {
                                    $clases .= 'text-gray-300 bg-white';
                                } elseif ($pasado) {
                                    $clases .= 'text-gray-400 bg-gray-100 cursor-not-allowed';
                                } elseif ($tieneAprobada) {
                                    $clases .= 'bg-red-100 text-red-700 font-semibold hover:bg-red-200';
                                } elseif ($tienePendiente) {
                                    $clases .= 'bg-yellow-100 text-yellow-800 font-semibold hover:bg-yellow-200';
                                } else {
                                    $clases .= 'bg-green-50 text-green-700 hover:bg-green-100';
                                }
                                if ($fecha === $clave) {
                                    $clases .= ' ring-2 ring-blue-600';
                                }
                            @endphp
                            <button type="button"
                                @disabled($pasado || ! $delMes)
                                wire:click="seleccionarDia('{{ $clave }}')"
                                class="{{ $clases }}">
                                {{ $dia->day }}
                            </button>
                        @endforeach
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-4 mt-4 text-xs text-gray-600">
                    <span class="flex items-center"><span class="w-3 h-3 rounded bg-green-100 mr-1"></span> Libre</span>
                    <span class="flex items-center"><span class="w-3 h-3 rounded bg-yellow-100 mr-1"></span> Solicitud pendiente</span>
                    <span class="flex items-center"><span class="w-3 h-3 rounded bg-red-100 mr-1"></span> Reservado</span>
                </div>

                <div class="mt-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Horarios ocupados este mes</h4>
                    @forelse($ocupadosPorDia->flatten() as $r)
                        <p class="text-sm text-gray-600">
                            {{ $r->fecha_inicio->format('d/m') }}
                            · {{ $r->fecha_inicio->format('H:i') }} - {{ $r->fecha_fin->format('H:i') }}
                            <span class="text-xs {{ $r->estado === 'aprobada' ? 'text-red-600' : 'text-yellow-700' }}">
                                ({{ $r->estado === 'aprobada' ? 'reservado' : 'pendiente' }})
                            </span>
                        </p>
                    @empty
                        <p class="text-sm text-gray-500">Sin reservas este mes. ¡Todo libre!</p>
                    @endforelse
                </div>
            @else
                <p class="mt-6 text-sm text-gray-500">Selecciona un equipo para ver su calendario de disponibilidad.</p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form wire:submit="reservar" class="space-y-5">
                <div>
                    <label for="fecha" class="block text-sm font-semibold text-gray-700 mb-2">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input id="fecha" type="date" wire:model="fecha" min="{{ today()->toDateString() }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('fecha') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="hora_inicio" class="block text-sm font-semibold text-gray-700 mb-2">
                            Hora de inicio <span class="text-red-500">*</span>
                        </label>
                        <input id="hora_inicio" type="time" wire:model="hora_inicio"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('hora_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="hora_fin" class="block text-sm font-semibold text-gray-700 mb-2">
                            Hora de fin <span class="text-red-500">*</span>
                        </label>
                        <input id="hora_fin" type="time" wire:model="hora_fin"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('hora_fin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="observaciones" class="block text-sm font-semibold text-gray-700 mb-2">
                        Motivo <span class="text-gray-400 text-xs">(opcional)</span>
                    </label>
                    <textarea id="observaciones" wire:model="observaciones" rows="3"
                        placeholder="Ej: Lo necesito para la exposición del curso de redes..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition duration-200">
                    Solicitar Reserva
                </button>
            </form>

            <p class="mt-4 text-xs text-gray-500">
                Tu reserva queda <strong>pendiente</strong> hasta que el encargado la apruebe.
            </p>
        </div>
    </div>

    <div class="mt-10">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Mis Reservas</h3>

        @if($misReservas->isEmpty())
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                Aún no has solicitado ninguna reserva.
            </div>
        @else
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                            <tr>
                                <th class="px-6 py-4">Equipo</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Horario</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($misReservas as $reserva)
                                @php
                                    $color = match($reserva->estado) {
                                        'aprobada' => 'bg-green-100 text-green-800',
                                        'pendiente' => 'bg-yellow-100 text-yellow-800',
                                        'rechazada' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-gray-900">{{ $reserva->equipo->codigo }}</span><br>
                                        <span class="text-sm text-gray-600">{{ $reserva->equipo->nombre }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $reserva->fecha_inicio->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $reserva->fecha_inicio->format('H:i') }} - {{ $reserva->fecha_fin->format('H:i') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $color }}">{{ ucfirst($reserva->estado) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if(in_array($reserva->estado, ['pendiente', 'aprobada']))
                                            <button wire:click="cancelar({{ $reserva->id }})"
                                                wire:confirm="¿Seguro que quieres cancelar esta reserva?"
                                                class="text-red-600 underline text-sm">Cancelar</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>