<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/ThemeController.php
| Описание: CRUD контроллер для управления темами (админка)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ThemeController extends Controller
{
    /**
     * Список всех тем
     * GET /api/admin/themes
     */
    public function index(): JsonResponse
    {
        $themes = Theme::withCount('products')
            ->orderBy('name')
            ->get();
            
        return response()->json($themes);
    }

    /**
     * Создание новой темы
     * POST /api/admin/themes
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:themes'
        ], [
            'name.required' => 'Название темы обязательно',
            'name.unique' => 'Тема с таким названием уже существует',
            'name.max' => 'Название не должно превышать 255 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $theme = Theme::create([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Тема успешно создана',
            'theme' => $theme
        ], 201);
    }

    /**
     * Получение темы по ID
     * GET /api/admin/themes/{id}
     */
    public function show(Theme $theme): JsonResponse
    {
        $theme->loadCount('products');
        return response()->json($theme);
    }

    /**
     * Обновление темы
     * PUT /api/admin/themes/{id}
     */
    public function update(Request $request, Theme $theme): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:themes,name,' . $theme->id
        ], [
            'name.required' => 'Название темы обязательно',
            'name.unique' => 'Тема с таким названием уже существует',
            'name.max' => 'Название не должно превышать 255 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $theme->update([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Тема успешно обновлена',
            'theme' => $theme
        ]);
    }

    /**
     * Удаление темы
     * DELETE /api/admin/themes/{id}
     */
    public function destroy(Theme $theme): JsonResponse
    {
        // Проверка наличия товаров с этой темой
        if ($theme->products()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить тему',
                'message' => 'К этой теме привязаны товары. Сначала измените тему у товаров.'
            ], 409);
        }

        $themeName = $theme->name;
        $theme->delete();

        return response()->json([
            'message' => "Тема '{$themeName}' успешно удалена"
        ]);
    }
}