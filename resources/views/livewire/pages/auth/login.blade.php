<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate([
            'form.email' => ['required', 'string', 'email'],
            'form.password' => ['required', 'string'],
        ]);

        $this->form->authenticate();

        Session::regenerate();

        // Cada rol entra siempre a su panel, aunque haya intentado otra URL.
        Session::forget('url.intended');
        $this->redirect(route(auth()->user()->dashboardRoute(), absolute: false), navigate: true);
    }
}; 
?>

<div>
    <h1 class="text-2xl font-bold text-gray-900">Iniciar sesión</h1>
    <p class="mt-2 mb-6 text-sm text-gray-600">Accede al sistema de préstamos con tu cuenta.</p>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />


    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>
        </div>

        <div class="mt-5 flex flex-col gap-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500 disabled:opacity-50" wire:loading.attr="disabled">
                Iniciar sesión
            </button>
        </div>
    </form>
    <div class="mt-6 border-t border-gray-200 pt-5 text-center">
        <p class="mb-3 text-sm text-gray-600">¿Todavía no tienes una cuenta?</p>
        <a href="{{ route('register') }}" wire:navigate class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border-2 border-blue-600 px-4 py-3 font-semibold text-blue-700 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">
            Crear cuenta
        </a>
    </div>
</div>
