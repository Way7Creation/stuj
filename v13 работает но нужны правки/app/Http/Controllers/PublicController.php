<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/PublicController.php
| Описание: Публичный контроллер для каталога и товаров (БЕЗ авторизации)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Theme;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    /**
     * Каталог товаров с фильтрами
     * GET /api/v1/catalog?cat=rings&theme=minimalism&attr=agate&sort=price_asc&page=1
     */
    public function catalog(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['theme', 'categories', 'attributes']);

        // Фильтр по категории
        if ($request->filled('cat')) {
            $query->inCategory($request->get('cat'));
        }

        // Фильтр по теме
        if ($request->filled('theme')) {
            if (is_numeric($request->get('theme'))) {
                $query->where('theme_id', $request->get('theme'));
            } else {
                $query->withTheme($request->get('theme'));
            }
        }

        // Фильтр по атрибуту
        if ($request->filled('attr')) {
            $query->withAttribute($request->get('attr'));
        }

        // Поиск по названию
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Фильтр только матрёшки
        if ($request->boolean('matryoshka')) {
            $query->matryoshka();
        }

        // Сортировка
        switch ($request->get('sort', 'name')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        // Пагинация
        $perPage = min($request->get('per_page', 12), 50); // Максимум 50
        $products = $query->paginate($perPage);

        // Трансформация данных
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => (float) $product->price,
                'use_matryoshka' => $product->use_matryoshka,
                'image_layers' => $product->image_layers,
                'gallery_images' => $product->gallery_images,
                'main_image' => $product->main_image,
                'theme' => $product->theme ? [
                    'id' => $product->theme->id,
                    'name' => $product->theme->name
                ] : null,
                'categories' => $product->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug
                    ];
                }),
                'attributes' => $product->attributes->map(function ($attribute) {
                    return [
                        'id' => $attribute->id,
                        'name' => $attribute->name
                    ];
                })
            ];
        });

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem()
            ],
            'filters' => [
                'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
                'themes' => Theme::orderBy('name')->get(['id', 'name']),
                'attributes' => Attribute::orderBy('name')->get(['id', 'name'])
            ]
        ]);
    }

    /**
     * Детали конкретного товара
     * GET /api/v1/product/{slug}
     */
    public function product(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->with(['theme', 'categories', 'attributes'])
            ->first();

        if (!$product) {
            return response()->json([
                'error' => 'Товар не найден'
            ], 404);
        }

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => (float) $product->price,
            'use_matryoshka' => $product->use_matryoshka,
            'image_layers' => $product->image_layers,
            'gallery_images' => $product->gallery_images,
            'main_image' => $product->main_image,
            'theme' => $product->theme ? [
                'id' => $product->theme->id,
                'name' => $product->theme->name
            ] : null,
            'categories' => $product->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug
                ];
            }),
            'attributes' => $product->attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'is_stone' => $attribute->isStone()
                ];
            }),
            'telegram_buy_link' => config('app.telegram_bot_username') 
                ? "https://t.me/" . config('app.telegram_bot_username') . "?start=order_{$product->id}"
                : null
        ];

        return response()->json($data);
    }
}