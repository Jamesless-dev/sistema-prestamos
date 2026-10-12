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
            <nav>
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-block px-5 py-2 border rounded-md font-medium">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-block px-5 py-2 font-semibold hover:underline text-base">
                        Iniciar Sesión
                    </a>
                @endauth
            </nav>
        @endif
    </header>


    <main class="w-full max-w-4xl mx-auto my-auto bg-white dark:bg-[#161615] rounded-2xl shadow-xl border border-gray-100 overflow-hidden flex flex-row items-center justify-between p-8 lg:p-10 gap-8">
        
        <div class="flex-1 w-full">
            <h1 class="text-2xl lg:text-3xl font-extrabold mb-3 text-gray-900 dark:text-white">
                Hola soy Kibo
            </h1>
            <p class="text-sm lg:text-base text-gray-600 dark:text-gray-300 leading-relaxed mb-5">
                Este sistema ha sido diseñado para centralizar y agilizar el control del inventario tecnológico de manera eficiente. Al iniciar sesión, la plataforma reconocerá automáticamente tu tipo de usuario para redirigirte a tu panel asignado, garantizando un acceso seguro, ordenado y adaptado a las necesidades de tu perfil institucional. <br><br>
            </p>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">
                ¡Inicia sesión para empezar esta experiencia!
            </p>
        </div>

        <div class="flex-shrink-0 flex items-center justify-center">
            <img src="{{ asset('imagenes/kibo1.png') }}" 
                 alt="Sistema de Préstamo de Equipos" 
                 class="h-72 w-auto object-contain rounded-2xl shadow-sm">
        </div>

    </main>
    <footer class="w-full"></footer>

</body>
</html>