<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Стужа') }} - Украшения с характером</title>
    
    <!-- SEO мета-теги -->
    <meta name="description" content="Стужа - уникальные украшения с натуральными камнями. Подберите идеальное украшение по астрологии.">
    <meta name="keywords" content="украшения, кольца, браслеты, натуральные камни, астрология, подарки">
    
    <!-- Open Graph теги -->
    <meta property="og:title" content="Стужа - Украшения с характером">
    <meta property="og:description" content="Уникальные украшения с натуральными камнями. Подберите идеальное украшение по астрологии.">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:type" content="website">
    
    <!-- Фавиконы -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    
    <!-- Preconnect для оптимизации загрузки -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Шрифты -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['client/src/main.js'])
</head>
<body class="bg-stuzha-bg text-stuzha-text antialiased">
    <div id="app"></div>
    
    <!-- Noscript fallback -->
    <noscript>
        <div style="text-align: center; padding: 50px; color: #fff;">
            <h1>JavaScript требуется</h1>
            <p>Для работы сайта необходимо включить JavaScript в вашем браузере.</p>
        </div>
    </noscript>
</body>
</html>