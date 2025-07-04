<?php
// routes/web.php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Здесь регистрируются маршруты для веб-интерфейса. Все маршруты
| автоматически получают middleware группу "web" из RouteServiceProvider.
*/

// SPA - все маршруты обрабатывает Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');