<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel del Estudiante
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Bienvenido, {{ auth()->user()->name }}</h3>
                    <p class="mb-6">Tu rol: <span class="font-bold text-green-600">{{ ucfirst(auth()->user()->role) }}</span></p>
                    
                    <a href="{{ route('reservas.estudiante') }}" class="inline-flex items-center px-4 py-2 mb-6 bg-blue-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">Reservar un equipo</a>
                    @livewire('consulta-equipos')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>