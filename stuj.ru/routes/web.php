<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Здесь регистрируются веб-маршруты для приложения. Эти маршруты
| загружаются RouteServiceProvider и назначаются группе "web".
|
*/

// Главная страница и SPA роуты обрабатываются Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

// Специальные роуты для SEO
Route::get('/sitemap.xml', function () {
    // Будет реализовано в SEOService
    return response()->view('sitemap')->header('Content-Type', 'text/xml');
})->name('sitemap');

Route::get('/robots.txt', function () {
    $content = "User-agent: *\n";
    $content .= "Allow: /\n";
    $content .= "Sitemap: " . url('/sitemap.xml') . "\n";
    
    return response($content, 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');