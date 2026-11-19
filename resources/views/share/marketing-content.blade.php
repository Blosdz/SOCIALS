<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c0d10">
    <title>{{ $marketingContent->title }} · FIREFLYONE</title>
    <meta name="description" content="{{ $marketingContent->description }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $marketingContent->title }}">
    <meta property="og:description" content="{{ $marketingContent->description }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    @if ($shareImage)
        <meta property="og:image" content="{{ $shareImage }}">
    @endif
    <meta name="twitter:card" content="{{ $shareImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $marketingContent->title }}">
    <meta name="twitter:description" content="{{ $marketingContent->description }}">
    @if ($shareImage)
        <meta name="twitter:image" content="{{ $shareImage }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#0c0d10] px-4 py-10 font-sans text-[#dedfe4]">
    <main class="w-full max-w-xl overflow-hidden rounded-2xl border border-[#25272e] bg-[#141519] shadow-2xl">
        @if ($shareImage)
            <img class="aspect-[16/8] w-full object-cover" src="{{ $shareImage }}" alt="{{ $marketingContent->title }}">
        @endif
        <div class="p-6 sm:p-8">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#f58220]">FIREFLYONE · Contenido para asesores</p>
            <h1 class="mt-3 text-2xl font-semibold leading-tight text-[#f0f2f5]">{{ $marketingContent->title }}</h1>
            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-[#9ea2af]">{{ $marketingContent->description }}</p>
            @if ($purchaseUrl)
                <a class="mt-6 inline-flex items-center gap-2 rounded-lg bg-[#f58220] px-5 py-3 text-sm font-semibold text-black transition hover:bg-[#e07116]" href="{{ $purchaseUrl }}" rel="noopener noreferrer">
                    Ver información y opciones de compra
                    <span aria-hidden="true">↗</span>
                </a>
            @endif
        </div>
    </main>
</body>
</html>
