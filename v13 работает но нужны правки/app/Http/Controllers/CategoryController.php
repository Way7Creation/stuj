<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/CategoryController.php
| Описание: CRUD контроллер для управления категориями (админка)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Список всех категорий
     * GET /api/admin/categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();
            
        return response()->json($categories);
    }

    /**
     * Создание новой категории
     * POST /api/admin/categories
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'slug' => 'nullable|string|max:255|unique:categories'
        ], [
            'name.required' => 'Название категории обязательно',
            'name.unique' => 'Категория с таким названием уже существует',
            'name.max' => 'Название не должно превышать 255 символов',
            'slug.unique' => 'Слаг уже используется другой категорией'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Генерация слага если не указан
        $slug = $request->slug ?: Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        // Проверка уникальности слага
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug
        ]);

        return response()->json([
            'message' => 'Категория успешно создана',
            'category' => $category
        ], 201);
    }

    /**
     * Получение категории по ID
     * GET /api/admin/categories/{id}
     */
    public function show(Category $category): JsonResponse
    {
        $category->loadCount('products');
        $category->load(['products' => function ($query) {
            $query->select('products.id', 'products.name', 'products.slug', 'products.price')
                  ->orderBy('products.name');
        }]);
        
        return response()->json($category);
    }

    /**
     * Обновление категории
     * PUT /api/admin/categories/{id}
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id
        ], [
            'name.required' => 'Название категории обязательно',
            'name.unique' => 'Категория с таким названием уже существует',
            'name.max' => 'Название не должно превышать 255 символов',
            'slug.unique' => 'Слаг уже используется другой категорией'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Обновление слага, если изменилось название
        $slug = $category->slug;
        if ($request->filled('slug')) {
            $slug = $request->slug;
        } elseif ($request->name !== $category->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $category->update([
            'name' => $request->name,
            'slug' => $slug
        ]);

        return response()->json([
            'message' => 'Категория успешно обновлена',
            'category' => $category
        ]);
    }

    /**
     * Удаление категории
     * DELETE /api/admin/categories/{id}
     */
    public function destroy(Category $category): JsonResponse
    {
        // Проверка наличия товаров в категории
        if ($category->products()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить категорию',
                'message' => 'В этой категории есть товары. Сначала переместите или удалите товары.'
            ], 409);
        }

        $categoryName = $category->name;
        $category->delete();

        return response()->json([
            'message' => "Категория '{$categoryName}' успешно удалена"
        ]);
    }

    /**
     * Массовое удаление категорий
     * POST /api/admin/categories/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка наличия товаров в категориях
        $categoriesWithProducts = Category::whereIn('id', $request->ids)
            ->whereHas('products')
            ->pluck('name')
            ->toArray();

        if (!empty($categoriesWithProducts)) {
            return response()->json([
                'error' => 'Невозможно удалить категории',
                'message' => 'В следующих категориях есть товары: ' . implode(', ', $categoriesWithProducts)
            ], 409);
        }

        $deletedCount = Category::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => "Удалено категорий: {$deletedCount}"
        ]);
    }
}