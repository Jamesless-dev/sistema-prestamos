<div class="max-w-6xl mx-auto p-6">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Equipos Disponibles</h2>
        <p class="text-gray-600 mt-2">Consulta el inventario del laboratorio</p>
    </div>

    <!-- Barra de busqueda -->
    <div class="mb-6">
        <div class="relative">
            <input 
                type="text" 
                wire:model.live="busqueda"
                placeholder="Buscar por nombre, código o categoría..."
                class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
            >
            <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Contador de resultados -->
    <div class="mb-4 text-sm text-gray-600">
        <span class="font-semibold text-blue-600">{{ $equiposDisponibles->count() }}</span> equipos disponibles
    </div>

    <!-- Lista de equipos -->
    @if($equiposDisponibles->isEmpty())
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p class="text-gray-600 text-lg">No hay equipos disponibles en este momento</p>
            <p class="text-gray-500 text-sm mt-2">Todos los equipos están prestados o no hay inventario registrado</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($equiposDisponibles as $equipo)
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition duration-200 overflow-hidden border border-gray-200">
                    <!-- Header con categoría -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3">
                        <span class="text-white text-xs font-semibold uppercase tracking-wide">
                            {{ $equipo->categoria }}
                        </span>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-bold text-gray-900">{{ $equipo->nombre }}</h3>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                </svg>
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $equipo->codigo }}</span>
                            </div>
                            
                            <div class="flex items-center text-sm text-green-600 font-semibold">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Disponible
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif


    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            <strong>ℹ️ Nota:</strong> Esta vista es solo de consulta. Para solicitar un equipo, acércate al encargado del laboratorio.
        </p>
    </div>
</div>