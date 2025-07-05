<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/CategoryController.php
| Описание: CRUD контроллер для управления категориями с поддержкой иерархии
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
     * Список всех категорий (плоский список)
     * GET /api/admin/categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
            
        return response()->json($categories);
    }

    /**
     * Дерево категорий (иерархическая структура)
     * GET /api/admin/categories/tree
     */
    public function tree(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->with(['children' => function($query) {
                $query->withCount('products')->orderBy('sort_order')->orderBy('name');
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
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
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500'
        ], [
            'name.required' => 'Название категории обязательно',
            'name.max' => 'Название не должно превышать 255 символов',
            'parent_id.exists' => 'Родительская категория не найдена',
            'sort_order.integer' => 'Порядок сортировки должен быть числом',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
            'meta_title.max' => 'Мета-заголовок не должен превышать 255 символов',
            'meta_description.max' => 'Мета-описание не должно превышать 500 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка на циклическую зависимость
        if ($request->filled('parent_id')) {
            $parentId = $request->parent_id;
            $parent = Category::find($parentId);
            
            if (!$parent) {
                return response()->json([
                    'error' => 'Родительская категория не найдена'
                ], 422);
            }

            // Проверяем, что родительская категория не является дочерней сама по себе
            if ($parent->parent_id !== null) {
                // В текущей схеме поддерживаем только 2 уровня вложенности
                return response()->json([
                    'error' => 'Нельзя создать категорию третьего уровня'
                ], 422);
            }
        }

        // Генерация слага
        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description
        ]);

        $category->loadCount('products');

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
        $category->load(['parent', 'children' => function($query) {
            $query->withCount('products');
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
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500'
        ], [
            'name.required' => 'Название категории обязательно',
            'name.max' => 'Название не должно превышать 255 символов',
            'parent_id.exists' => 'Родительская категория не найдена',
            'sort_order.integer' => 'Порядок сортировки должен быть числом',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
            'meta_title.max' => 'Мета-заголовок не должен превышать 255 символов',
            'meta_description.max' => 'Мета-описание не должно превышать 500 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка на циклическую зависимость
        if ($request->filled('parent_id')) {
            $parentId = $request->parent_id;
            
            // Нельзя сделать категорию родителем самой себя
            if ($parentId == $category->id) {
                return response()->json([
                    'error' => 'Категория не может быть родителем самой себя'
                ], 422);
            }

            $parent = Category::find($parentId);
            if (!$parent) {
                return response()->json([
                    'error' => 'Родительская категория не найдена'
                ], 422);
            }

            // Проверяем на циклические зависимости
            if ($parent->parent_id == $category->id) {
                return response()->json([
                    'error' => 'Обнаружена циклическая зависимость'
                ], 422);
            }

            // Проверяем уровень вложенности
            if ($parent->parent_id !== null) {
                return response()->json([
                    'error' => 'Нельзя создать категорию третьего уровня'
                ], 422);
            }
        }

        // Обновление слага если изменилось название
        if ($request->name !== $category->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $category->slug = $slug;
        }

        $category->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description
        ]);

        $category->loadCount('products');

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
        // Проверяем есть ли дочерние категории
        if ($category->children()->count() > 0) {
            return response()->json([
                'error' => 'Нельзя удалить категорию, у которой есть дочерние категории. Сначала удалите или переместите дочерние категории.'
            ], 409);
        }

        // Проверяем есть ли связанные товары
        $productsCount = $category->products()->count();
        if ($productsCount > 0) {
            return response()->json([
                'error' => "Нельзя удалить категорию, к которой привязано товаров: {$productsCount}. Сначала измените категории у товаров."
            ], 409);
        }

        $category->delete();

        return response()->json([
            'message' => 'Категория успешно удалена'
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
            'ids.*' => 'integer|exists:categories,id'
        ], [
            'ids.required' => 'Необходимо выбрать категории для удаления',
            'ids.array' => 'Неверный формат данных',
            'ids.min' => 'Необходимо выбрать хотя бы одну категорию',
            'ids.*.exists' => 'Одна или несколько категорий не найдены'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $ids = $request->ids;
        $categories = Category::whereIn('id', $ids)->get();

        $errors = [];
        $deleted = 0;

        foreach ($categories as $category) {
            // Проверяем дочерние категории
            if ($category->children()->count() > 0) {
                $errors[] = "Категория '{$category->name}' имеет дочерние категории";
                continue;
            }

            // Проверяем связанные товары
            $productsCount = $category->products()->count();
            if ($productsCount > 0) {
                $errors[] = "Категория '{$category->name}' имеет {$productsCount} товаров";
                continue;
            }

            $category->delete();
            $deleted++;
        }

        $message = "Удалено категорий: {$deleted}";
        if (!empty($errors)) {
            $message .= ". Ошибки: " . implode('; ', $errors);
        }

        return response()->json([
            'message' => $message,
            'deleted' => $deleted,
            'errors' => $errors
        ]);
    }
}