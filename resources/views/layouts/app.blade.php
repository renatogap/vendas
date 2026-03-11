<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#ff6b35">
        <meta name="description" content="Sistema de vendas rapido para registrar consumo por cliente com acompanhamento mensal.">
        <title>@yield('title', 'Vendas')</title>
        <meta property="og:title" content="@yield('title', 'Vendas')">
        <meta property="og:description" content="Sistema de vendas rapido para registrar consumo por cliente com acompanhamento mensal.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('icon-512.png') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="@yield('title', 'Vendas')">
        <meta name="twitter:description" content="Sistema de vendas rapido para registrar consumo por cliente com acompanhamento mensal.">
        <meta name="twitter:image" content="{{ asset('icon-512.png') }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('icon-512.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20,500,1,0" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body>
        <div class="bg-glow"></div>
        <main class="app-shell">
            @yield('content')
        </main>
    </body>
</html>