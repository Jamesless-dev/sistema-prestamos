@csrf

<div>
    <x-input-label for="codigo" value="Código" />
    <x-text-input id="codigo" name="codigo" type="text" class="block mt-1 w-full" :value="old('codigo', $equipo->codigo)" required maxlength="255" autofocus />
    <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="nombre" value="Nombre" />
    <x-text-input id="nombre" name="nombre" type="text" class="block mt-1 w-full" :value="old('nombre', $equipo->nombre)" required maxlength="255" />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="categoria" value="Categoría" />
    <x-text-input id="categoria" name="categoria" type="text" class="block mt-1 w-full" :value="old('categoria', $equipo->categoria)" required maxlength="255" />
    <x-input-error :messages="$errors->get('categoria')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="estado" value="Estado" />
    <select id="estado" name="estado" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
        @foreach (\App\Models\Equipo::ESTADOS as $estado)
            <option value="{{ $estado }}" @selected(old('estado', $equipo->estado ?? \App\Models\Equipo::DISPONIBLE) === $estado)>{{ ucfirst($estado) }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('estado')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-6">
    <x-primary-button>{{ $textoBoton }}</x-primary-button>
    <a href="{{ route('equipos.index') }}" class="text-gray-600 underline">Cancelar</a>
</div>
