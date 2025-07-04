<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ПРОСТЫЕ ТЕСТОВЫЕ РОУТЫ БЕЗ КОНТРОЛЛЕРОВ
Route::get('/catalog', function() {
    return response()->json([
        'message' => 'Catalog API works!',
        'data' => [],
        'test' => true,
        'time' => date('Y-m-d H:i:s')
    ]);
});

Route::post('/admin/login', function(Request $request) {
    return response()->json([
        'message' => 'Login endpoint works',
        'email' => $request->input('email'),
        'token' => 'test_token_123',
        'user' => ['name' => 'Test Admin', 'email' => 'admin@stuj.ru']
    ]);
});

Route::get('/admin/categories', function() {
    return response()->json([
        'categories' => [
            ['id' => 1, 'name' => 'Кольца', 'slug' => 'rings'],
            ['id' => 2, 'name' => 'Серьги', 'slug' => 'earrings'],
            ['id' => 3, 'name' => 'Браслеты', 'slug' => 'bracelets']
        ]
    ]);
});

Route::get('/admin/themes', function() {
    return response()->json([
        'themes' => [
            ['id' => 1, 'name' => 'Минимализм'],
            ['id' => 2, 'name' => 'Классика'],
            ['id' => 3, 'name' => 'Модерн']
        ]
    ]);
});

Route::get('/admin/products', function() {
    return response()->json([
        'data' => [],
        'meta' => [
            'total' => 0,
            'per_page' => 15,
            'current_page' => 1
        ]
    ]);
});

Route::get('/admin/attributes', function() {
    return response()->json([
        'attributes' => [
            ['id' => 1, 'name' => 'агат'],
            ['id' => 2, 'name' => 'турмалин'],
            ['id' => 3, 'name' => 'серебро']
        ]
    ]);
});

Route::get('/product/{slug}', function($slug) {
    return response()->json([
        'product' => [
            'slug' => $slug,
            'name' => 'Тестовый товар',
            'price' => 2500,
            'description' => 'Описание товара'
        ]
    ]);
});

Route::post('/quiz', function(Request $request) {
    return response()->json([
        'recommended_stones' => ['агат', 'турмалин'],
        'products' => []
    ]);
});