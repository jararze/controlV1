<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Logístico</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Animaciones personalizadas */
        .fade-in {
            animation: fadeIn 1.5s ease-in-out forwards;
            opacity: 0;
        }

        .slide-up {
            animation: slideUp 1.2s ease-out forwards;
            opacity: 0;
            transform: translateY(50px);
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 font-sans">
<!-- Contenedor Principal -->
<div class="flex min-h-screen items-center justify-center">

    <!-- Contenido Principal -->
    <div class="w-full max-w-2xl p-8 space-y-8">

        <!-- Encabezado -->
        <header class="text-center">
            <h1 class="text-4xl font-bold text-white fade-in">Bienvenido a Control Logístico</h1>
            <p class="text-gray-400 mt-2 slide-up">Gestión eficiente y simplificada para tus necesidades logísticas. Conéctate para comenzar a organizar y optimizar tus operaciones.</p>
        </header>

        <!-- Tarjeta Principal de Bienvenida -->
        <section class="bg-gray-800 p-8 rounded-lg shadow-lg slide-up text-center space-y-6">
            <div class="flex justify-center">
                <img src="{{ asset('assets/media/images/dall.webp') }}" alt="Logística" class="w-36 h-36 rounded-full shadow-lg">
            </div>
            <h2 class="text-2xl font-semibold text-gray-100">Gestiona tus operaciones desde una sola plataforma</h2>
            <p class="text-gray-400">Accede a herramientas diseñadas para mejorar la comunicación, organización de tareas y monitoreo de progresos de manera efectiva.</p>

            <!-- Botones de Login y Register / Dashboard -->
            @if (Route::has('login'))
                <nav class="space-x-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-300">Login</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-gray-700 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-300">Register</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </section>
    </div>
</div>
</body>
</html>
