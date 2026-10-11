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
</div>