<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Стужа') }}</title>
    
    <!-- SEO мета-теги -->
    <meta name="description" content="Интернет-магазин украшений Стужа - уникальные украшения с эффектом матрёшки">
    <meta name="keywords" content="украшения, кольца, браслеты, серьги, агат, турмалин">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ config('app.name', 'Стужа') }}">
    <meta property="og:description" content="Интернет-магазин украшений Стужа">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:image" content="{{ config('app.url') }}/images/og-image.jpg">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Шрифты -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vite Assets (ИСПРАВЛЕНО: правильный путь) -->
    @vite(['client/src/main.js'])
</head>
<body class="bg-stuzha-bg text-stuzha-text antialiased">
    <!-- Vue.js App Root -->
    <div id="app">
        <!-- Прелоадер -->
        <div class="min-h-screen bg-stuzha-bg flex items-center justify-center">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-stuzha-accent mx-auto mb-4"></div>
                <p class="text-gray-400">Загружаем магазин украшений...</p>
            </div>
        </div>
    </div>

    <!-- Дополнительные скрипты -->
    <script>
        // Глобальные переменные для приложения
        window.APP_CONFIG = {
            apiUrl: '{{ config("app.url") }}/api',
            appUrl: '{{ config("app.url") }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
</body>
</html>