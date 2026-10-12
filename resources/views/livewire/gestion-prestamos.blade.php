<div class="max-w-4xl mx-auto p-6">

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Registro de Préstamo</h2>
        <p class="text-gray-600 mt-2">Asigna un equipo disponible a un estudiante</p>
    </div>

    @if($mensajeExito)
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-green-700 font-medium">{{ $mensajeExito }}</p>
            </div>
        </div>
    @endif

    @if($mensajeError)
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <p class="text-red-700 font-medium">{{ $mensajeError }}</p>
            </div>
        </div>
    @endif

    <!-- Formulario -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form wire:submit="registrarPrestamo" class="space-y-6">
            
            <!-- Selección de Equipo -->
            <div>
                <label for="equipo" class="block text-sm font-semibold text-gray-700 mb-2">
                    Equipo a Prestar <span class="text-red-500">*</span>
                </label>
                <select 
                    id="equipo" 
                    wire:model="equipo_id" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white"
                >
                    <option value="">-- Selecciona un equipo disponible --</option>
                    @forelse($equiposDisponibles as $equipo)
                        <option value="{{ $equipo->id }}">
                            {{ $equipo->codigo }} - {{ $equipo->nombre }} ({{ $equipo->categoria }})
                        </option>
                    @empty
                        <option value="" disabled>No hay equipos disponibles</option>
                    @endforelse
                </select>
                @error('equipo_id') 
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Selección de Estudiante -->
            <div>
                <label for="estudiante" class="block text-sm font-semibold text-gray-700 mb-2">
                    Estudiante <span class="text-red-500">*</span>
                </label>
                <select 
                    id="estudiante" 
                    wire:model="estudiante_id" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 bg-white"
                >
                    <option value="">-- Selecciona un estudiante --</option>
                    @foreach($estudiantes as $estudiante)
                        <option value="{{ $estudiante->id }}">
                            {{ $estudiante->name }} ({{ $estudiante->email }})
                        </option>
                    @endforeach
                </select>
                @error('estudiante_id') 
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Observaciones -->
            <div>
                <label for="observaciones" class="block text-sm font-semibold text-gray-700 mb-2">
                    Observaciones <span class="text-gray-400 text-xs">(opcional)</span>
                </label>
                <textarea 
                    id="observaciones" 
                    wire:model="observaciones" 
                    rows="3"
                    placeholder="Ej: Se entrega con cargador y mouse. El equipo presenta un rayón en la esquina..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                ></textarea>
                @error('observaciones') 
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botón de Acción -->
            <div class="pt-4">
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition duration-200 flex items-center justify-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Registrar Préstamo
                </button>
            </div>

        </form>
    </div>


    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            <strong> Consejo:</strong> Solo se muestran los equipos con estado "Disponible". 
            Al registrar el préstamo, el equipo cambiará automáticamente a "Prestado".
        </p>
    </div>


    <div class="mt-10">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Préstamos Activos (Pendientes de Devolución)</h3>
        
        @if($prestamosActivos->isEmpty())
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p>No hay equipos prestados en este momento. ¡Todo está en el laboratorio!</p>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                            <tr>
                                <th class="px-6 py-4">Equipo</th>
                                <th class="px-6 py-4">Estudiante</th>
                                <th class="px-6 py-4">Fecha Préstamo</th>
                                <th class="px-6 py-4">Observaciones</th>
                                <th class="px-6 py-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($prestamosActivos as $prestamo)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-gray-900">{{ $prestamo->equipo->codigo }}</span>
                                        <br>
                                        <span class="text-sm text-gray-600">{{ $prestamo->equipo->nombre }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        {{ $prestamo->estudiante->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $prestamo->fecha_prestamo->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                        {{ $prestamo->observaciones ?: 'Sin observaciones' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button 
                                            wire:click="registrarDevolucion({{ $prestamo->id }})"
                                            wire:confirm="¿Estás seguro de que el estudiante ha devuelto este equipo?"
                                            class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-sm hover:shadow transition duration-200 flex items-center mx-auto"
                                        >
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            Devolver
                                        </button>
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