<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="@yield('theme-color', '#0b0c10')">
    <title>@yield('title', 'FIREFLYONE')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @yield('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col pb-10 @yield('body-class')">
    @yield('content')
    <footer class="fixed inset-x-0 bottom-0 z-30 flex h-10 items-center justify-center border-t border-[#1c1e27] bg-[#0b0c10] px-4 text-center text-xs text-[#94a3b8]">
        <p>FIREFLYONE · <span class="hidden sm:inline">Plataforma de comunicación para asesores · </span><a class="underline hover:text-white" href="{{ route('privacy.policy') }}">Privacidad</a> · <a class="underline hover:text-white" href="{{ route('terms') }}">Términos</a></p>
    </footer>
</body>
</html>
