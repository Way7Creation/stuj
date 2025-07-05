<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Список всех категорий (плоский список)
     * GET /api/admin/categories
     */
    public function index(): JsonResponse
    {
        try {
            $categories = Category::withCount('products')
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
                
            return response()->json($categories);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка загрузки категорий: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки категорий',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Дерево категорий (иерархическая структура)
     * GET /api/admin/categories/tree
     */
    public function tree(): JsonResponse
    {
        try {
            $categories = Category::withCount('products')
                ->with(['children' => function($query) {
                    $query->withCount('products')->orderBy('sort_order')->orderBy('name');
                }])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
                
            return response()->json($categories);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка загрузки дерева категорий: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки дерева категорий',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение категории по ID
     * GET /api/admin/categories/{id}
     */
    public function show(Category $category): JsonResponse
    {
        try {
            $category->loadCount('products');
            $category->load(['parent', 'children']);
            
            return response()->json($category);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка загрузки категории: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки категории',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создание новой категории
     * POST /api/admin/categories
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->wouldCreateCycle(null, $value)) {
                        $fail('Выбранная родительская категория создаст циклическую зависимость.');
                    }
                }
            ],
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

        try {
            // Генерация slug
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
            
        } catch (\Exception $e) {
            \Log::error('Ошибка создания категории: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка создания категории',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ИСПРАВЛЕНО: Обновление категории с полной валидацией
     * PUT /api/admin/categories/{id}
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                'not_in:' . $category->id, // Категория не может быть родителем самой себе
                function ($attribute, $value, $fail) use ($category) {
                    if ($value && $this->wouldCreateCycle($category->id, $value)) {
                        $fail('Выбранная родительская категория создаст циклическую зависимость.');
                    }
                }
            ],
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500'
        ], [
            'name.required' => 'Название категории обязательно',
            'name.max' => 'Название не должно превышать 255 символов',
            'parent_id.exists' => 'Родительская категория не найдена',
            'parent_id.not_in' => 'Категория не может быть родителем самой себе',
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

        try {
            // Обновляем slug только если изменилось название
            $updateData = [
                'name' => $request->name,
                'parent_id' => $request->parent_id,
                'sort_order' => $request->sort_order ?? 0,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description
            ];

            if ($request->name !== $category->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;

                while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $updateData['slug'] = $slug;
            }

            $category->update($updateData);
            $category->loadCount('products');

            return response()->json([
                'message' => 'Категория успешно обновлена',
                'category' => $category
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка обновления категории: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка обновления категории',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удаление категории
     * DELETE /api/admin/categories/{id}
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
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
            
        } catch (\Exception $e) {
            \Log::error('Ошибка удаления категории: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка удаления категории',
                'message' => $e->getMessage()
            ], 500);
        }
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
            'ids.min' => 'Необходимо выбрать хотя бы одну категорию'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categories = Category::whereIn('id', $request->ids)
                ->with(['children', 'products'])
                ->get();

            $deleted = 0;
            $errors = [];

            foreach ($categories as $category) {
                // Проверяем дочерние категории
                if ($category->children->count() > 0) {
                    $errors[] = "Категория '{$category->name}' имеет дочерние категории";
                    continue;
                }

                // Проверяем товары
                if ($category->products->count() > 0) {
                    $errors[] = "Категория '{$category->name}' содержит товары ({$category->products->count()})";
                    continue;
                }

                $category->delete();
                $deleted++;
            }

            return response()->json([
                'message' => "Удалено категорий: {$deleted}",
                'deleted_count' => $deleted,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка массового удаления категорий: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка удаления категорий',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * НОВОЕ: Проверка на циклические зависимости
     */
    private function wouldCreateCycle(?int $categoryId, int $parentId): bool
    {
        // Если категории еще нет (создание), циклов быть не может
        if (!$categoryId) {
            return false;
        }

        // Ищем путь от предполагаемого родителя до корня
        $currentId = $parentId;
        $visitedIds = [];

        while ($currentId) {
            // Если встретили проверяемую категорию, то есть цикл
            if ($currentId === $categoryId) {
                return true;
            }

            // Защита от бесконечного цикла
            if (in_array($currentId, $visitedIds)) {
                return true;
            }

            $visitedIds[] = $currentId;

            // Получаем родителя текущей категории
            $parent = Category::find($currentId);
            $currentId = $parent ? $parent->parent_id : null;
        }

        return false;
    }

    /**
     * НОВОЕ: Получить все дочерние категории (рекурсивно)
     * GET /api/admin/categories/{id}/descendants
     */
    public function getDescendants(Category $category): JsonResponse
    {
        try {
            $descendants = $this->collectDescendants($category);
            
            return response()->json([
                'category' => $category,
                'descendants' => $descendants,
                'total_descendants' => count($descendants)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка получения потомков категории: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка получения потомков категории',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Рекурсивный сбор всех потомков категории
     */
    private function collectDescendants(Category $category): array
    {
        $descendants = [];
        
        foreach ($category->children()->with('children')->get() as $child) {
            $descendants[] = [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'level' => 1
            ];
            
            // Рекурсивно добавляем потомков
            foreach ($this->collectDescendants($child) as $grandChild) {
                $grandChild['level']++;
                $descendants[] = $grandChild;
            }
        }
        
        return $descendants;
    }
}