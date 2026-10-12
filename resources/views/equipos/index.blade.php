<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Equipos</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <p role="status" class="mb-4 text-green-700">{{ session('success') }}</p>
                    @endif
                    @if (session('error'))
                        <p role="alert" class="mb-4 text-red-700">{{ session('error') }}</p>
                    @endif

                    <a href="{{ route('equipos.create') }}" class="inline-flex items-center px-4 py-2 mb-6 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Registrar equipo</a>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead class="border-b bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Código</th>
                                    <th scope="col" class="px-4 py-3">Nombre</th>
                                    <th scope="col" class="px-4 py-3">Categoría</th>
                                    <th scope="col" class="px-4 py-3">Estado</th>
                                    <th scope="col" class="px-4 py-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($equipos as $equipo)
                                    <tr class="border-b">
                                        <td class="px-4 py-3">{{ $equipo->codigo }}</td>
                                        <td class="px-4 py-3">{{ $equipo->nombre }}</td>
                                        <td class="px-4 py-3">{{ $equipo->categoria }}</td>
                                        <td class="px-4 py-3">{{ ucfirst($equipo->estado) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-4">
                                                <a href="{{ route('equipos.edit', $equipo) }}" class="text-indigo-600 underline" aria-label="Editar {{ $equipo->codigo }}">Editar</a>
                                                <form method="POST" action="{{ route('equipos.destroy', $equipo) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este equipo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 underline" aria-label="Eliminar {{ $equipo->codigo }}">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-gray-500">No hay equipos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">{{ $equipos->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
