<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>emPet — Reencontre quem você ama</title>
    <meta name="description" content="O emPet conecta automaticamente quem perdeu com quem encontrou um pet. Reporte, seja notificado e reencontre.">
    <link rel="canonical" href="{{ config('app.url') }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:title" content="Empet — Reencontre quem você ama">
    <meta property="og:description" content="O emPet conecta automaticamente quem perdeu com quem encontrou um pet. Reporte, seja notificado e reencontre.">
    {{-- <meta property="og:image" content="{{ asset('images/og.png') }}"> --}}

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Empet — Reencontre quem você ama">
    <meta name="twitter:description" content="O Empet conecta automaticamente quem perdeu com quem encontrou um pet.">
    {{-- <meta name="twitter:image" content="{{ asset('images/og.png') }}"> --}}

    {{-- Favicon --}}
    {{-- <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}"> --}}

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/landing.css'])
</head>
<body class="font-sans text-dark antialiased">
    @yield('content')
</body>
</html>
