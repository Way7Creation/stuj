<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    /**
     * Список всех категорий (с поддержкой подкатегорий)
     * GET /api/admin/categories
     */
    public function index(): JsonResponse
    {
        try {
            // Проверяем, есть ли новые поля в БД
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');
            
            if ($hasNewFields) {
                // Новая структура с подкатегориями
                $categories = Category::with(['parent', 'children'])
                    ->withCount('products')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($category) {
                        return $this->formatCategory($category);
                    });
            } else {
                // Старая структура (обратная совместимость)
                $categories = Category::withCount('products')
                    ->orderBy('name')
                    ->get();
            }
                
            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки категорий: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка загрузки категорий',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * НОВОЕ: Получить дерево категорий
     * GET /api/admin/categories/tree
     */
    public function tree(): JsonResponse
    {
        try {
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');
            
            if ($hasNewFields) {
                // Новая структура - строим дерево
                $categories = Category::with('childrenRecursive')
                    ->whereNull('parent_id')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('name')
                    ->get();

                $tree = $this->buildTree($categories);
            } else {
                // Старая структура - просто список
                $categories = Category::orderBy('name')->get();
                $tree = $categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $cat->slug,
                        'depth' => 0,
                        'is_root' => true
                    ];
                });
            }
            
            return response()->json($tree);
        } catch (\Exception $e) {
            Log::error('Ошибка получения дерева категорий: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения дерева',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создание новой категории (с поддержкой подкатегорий)
     * POST /api/admin/categories
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');
            
            $rules = [
                'name' => 'required|string|max:255|unique:categories',
                'slug' => 'nullable|string|max:255|unique:categories'
            ];

            // Добавляем правила для новых полей если они есть
            if ($hasNewFields) {
                $rules = array_merge($rules, [
                    'parent_id' => 'nullable|exists:categories,id',
                    'sort_order' => 'nullable|integer|min:0',
                    'meta_title' => 'nullable|string|max:255',
                    'meta_description' => 'nullable|string|max:500'
                ]);
            }

            $validator = Validator::make($request->all(), $rules, [
                'name.required' => 'Название категории обязательно',
                'name.unique' => 'Категория с таким названием уже существует',
                'slug.unique' => 'Слаг уже используется другой категорией',
                'parent_id.exists' => 'Родительская категория не найдена'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Генерация слага
            $slug = $request->slug ?: Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Данные для создания
            $data = [
                'name' => $request->name,
                'slug' => $slug
            ];

            // Добавляем новые поля если они поддерживаются
            if ($hasNewFields) {
                $data = array_merge($data, [
                    'parent_id' => $request->parent_id,
                    'sort_order' => $request->sort_order ?? 0,
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description
                ]);
            }

            $category = Category::create($data);

            // Загружаем связи если возможно
            if ($hasNewFields) {
                try {
                    $category->load(['parent', 'children']);
                } catch (\Exception $e) {
                    // Игнорируем ошибки загрузки связей
                }
            }

            return response()->json([
                'message' => 'Категория успешно создана',
                'category' => $this->formatCategory($category)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Ошибка создания категории: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка создания категории',
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
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');
            
            if ($hasNewFields) {
                try {
                    $category->load(['parent', 'children', 'products']);
                } catch (\Exception $e) {
                    $category->loadCount('products');
                }
            } else {
                $category->loadCount('products');
            }
            
            return response()->json($this->formatCategory($category));
        } catch (\Exception $e) {
            Log::error('Ошибка получения категории: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения категории',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновление категории
     * PUT /api/admin/categories/{id}
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');
            
            $rules = [
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id
            ];

            if ($hasNewFields) {
                $rules = array_merge($rules, [
                    'parent_id' => 'nullable|exists:categories,id',
                    'sort_order' => 'nullable|integer|min:0',
                    'meta_title' => 'nullable|string|max:255',
                    'meta_description' => 'nullable|string|max:500'
                ]);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Ошибка валидации',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Проверка на цикличность для новой структуры
            if ($hasNewFields && $request->parent_id) {
                if ($request->parent_id == $category->id) {
                    return response()->json([
                        'error' => 'Категория не может быть родителем самой себя'
                    ], 422);
                }
            }

            // Обновление слага
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

            // Данные для обновления
            $data = [
                'name' => $request->name,
                'slug' => $slug
            ];

            if ($hasNewFields) {
                $data = array_merge($data, [
                    'parent_id' => $request->parent_id,
                    'sort_order' => $request->sort_order ?? $category->sort_order ?? 0,
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description
                ]);
            }

            $category->update($data);

            if ($hasNewFields) {
                try {
                    $category->load(['parent', 'children']);
                } catch (\Exception $e) {
                    // Игнорируем ошибки для старой структуры
                }
            }

            return response()->json([
                'message' => 'Категория успешно обновлена',
                'category' => $this->formatCategory($category)
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка обновления категории: ' . $e->getMessage());
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
            if ($category->products()->exists()) {
                return response()->json([
                    'error' => 'Невозможно удалить категорию',
                    'message' => 'К этой категории привязаны товары.'
                ], 409);
            }

            // Проверка подкатегорий для новой структуры
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');
            if ($hasNewFields) {
                try {
                    if ($category->children()->exists()) {
                        return response()->json([
                            'error' => 'Невозможно удалить категорию',
                            'message' => 'У этой категории есть подкатегории.'
                        ], 409);
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки для старой структуры
                }
            }

            $category->delete();

            return response()->json([
                'message' => 'Категория успешно удалена'
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка удаления категории: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка удаления категории',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk удаление
     * POST /api/admin/categories/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
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

            $categories = Category::whereIn('id', $request->ids)->get();
            $deleted = 0;
            $errors = [];
            $hasNewFields = Schema::hasColumn('categories', 'parent_id');

            foreach ($categories as $category) {
                $canDelete = true;
                
                if ($category->products()->exists()) {
                    $errors[] = "Категория '{$category->name}' имеет товары";
                    $canDelete = false;
                }

                if ($hasNewFields) {
                    try {
                        if ($category->children()->exists()) {
                            $errors[] = "Категория '{$category->name}' имеет подкатегории";
                            $canDelete = false;
                        }
                    } catch (\Exception $e) {
                        // Игнорируем для старой структуры
                    }
                }

                if ($canDelete) {
                    $category->delete();
                    $deleted++;
                }
            }

            return response()->json([
                'message' => "Удалено категорий: {$deleted}",
                'deleted_count' => $deleted,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка bulk удаления: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка удаления категорий',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     */
    private function formatCategory(Category $category): array
    {
        $hasNewFields = Schema::hasColumn('categories', 'parent_id');
        
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'products_count' => $category->products_count ?? 0,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at
        ];

        // Добавляем новые поля если они есть
        if ($hasNewFields) {
            try {
                $data = array_merge($data, [
                    'parent_id' => $category->parent_id,
                    'sort_order' => $category->sort_order ?? 0,
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                    'depth' => $category->depth ?? 0,
                    'is_root' => $category->isRoot(),
                    'has_children' => $category->hasChildren(),
                    'parent' => $category->parent ? [
                        'id' => $category->parent->id,
                        'name' => $category->parent->name,
                        'slug' => $category->parent->slug
                    ] : null,
                    'children' => $category->children ? $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug
                        ];
                    }) : [],
                    'breadcrumbs' => $category->breadcrumbs ?? []
                ]);
            } catch (\Exception $e) {
                // Игнорируем ошибки, используем базовые данные
            }
        }

        return $data;
    }

    private function buildTree($categories, $depth = 0): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            $item = [
                'id' => $category->id,
                'name' => str_repeat('— ', $depth) . $category->name,
                'slug' => $category->slug,
                'depth' => $depth,
                'is_root' => $depth === 0
            ];
            
            $tree[] = $item;
            
            try {
                if ($category->children && $category->children->count() > 0) {
                    $tree = array_merge($tree, $this->buildTree($category->children, $depth + 1));
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки рекурсии
            }
        }
        
        return $tree;
    }
}