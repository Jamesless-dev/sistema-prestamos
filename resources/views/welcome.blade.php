<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kibo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] min-h-screen flex flex-col justify-between p-6 lg:p-8">
    
    <header class="w-full flex justify-end text-sm">
        @if (Route::has('login'))
            <nav class="flex flex-wrap items-center justify-end gap-3" aria-label="Acceso al sistema">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">
                        Ir a mi panel
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-blue-600 px-4 py-3 font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-300 dark:hover:bg-blue-950 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">
                        Crear cuenta
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-5 py-3 text-base font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-500">
                        Iniciar Sesión
                    </a>
                @endauth
            </nav>
        @endif
    </header>


    <main class="w-full max-w-4xl mx-auto my-8 md:my-auto bg-white dark:bg-[#161615] rounded-2xl border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row items-center justify-between p-6 sm:p-8 lg:p-10 gap-8">
        
        <div class="min-w-0 flex-1 w-full">
            <h1 class="text-2xl lg:text-3xl font-extrabold mb-3 text-gray-900 dark:text-white">
                Hola soy Kibo
            </h1>
            <p class="text-base text-gray-600 dark:text-gray-300 leading-relaxed mb-5">
                Este sistema ha sido diseñado para centralizar y agilizar el control del inventario tecnológico de manera eficiente. Al iniciar sesión, la plataforma reconocerá automáticamente tu tipo de usuario para redirigirte a tu panel asignado, garantizando un acceso seguro, ordenado y adaptado a las necesidades de tu perfil institucional. <br><br>
            </p>
            <p class="text-sm font-bold text-blue-700 dark:text-blue-300">
                ¡Inicia sesión para empezar esta experiencia!
            </p>
        </div>

        <div class="w-full md:w-auto shrink-0 flex items-center justify-center">
            <img src="{{ asset('imagenes/kibo1.png') }}" 
                 alt="Sistema de Préstamo de Equipos" 
                 class="h-56 sm:h-72 w-auto max-w-full object-contain rounded-2xl">
        </div>

    </main>
    <footer class="w-full"></footer>

</body>
</html>
