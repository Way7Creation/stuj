<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/Category.php
| Описание: ПОЛНАЯ модель категорий с поддержкой иерархии
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'sort_order',
        'meta_title',
        'meta_description'
    ];

    /**
     * Типы полей
     */
    protected $casts = [
        'sort_order' => 'integer'
    ];

    /**
     * Связь с родительской категорией
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Связь с дочерними категориями
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Связь с товарами (многие ко многим)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    /**
     * Scope: только корневые категории
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: с дочерними категориями
     */
    public function scopeWithChildren(Builder $query): Builder
    {
        return $query->with(['children' => function ($q) {
            $q->orderBy('sort_order');
        }]);
    }

    /**
     * Автоматическая генерация slug при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Получить количество товаров в категории
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Получить полный путь категории
     */
    public function getFullPathAttribute(): string
    {
        $path = [];
        $category = $this;
        
        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Проверить, является ли категория корневой
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Проверить, имеет ли категория дочерние
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Получить все товары включая дочерние категории
     */
    public function getAllProducts()
    {
        $productIds = $this->products->pluck('id');
        
        foreach ($this->children as $child) {
            $productIds = $productIds->merge($child->products->pluck('id'));
        }
        
        return Product::whereIn('id', $productIds->unique());
    }

    /**
     * Получить хлебные крошки
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $category = $this;
        
        while ($category) {
            array_unshift($breadcrumbs, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug
            ]);
            $category = $category->parent;
        }
        
        return $breadcrumbs;
    }

    /**
     * Построить дерево категорий
     */
    public static function getTree(): \Illuminate\Support\Collection
    {
        return self::with(['children' => function ($query) {
            $query->withCount('products')->orderBy('sort_order');
        }])
        ->withCount('products')
        ->root()
        ->orderBy('sort_order')
        ->get();
    }

    /**
     * Проверить возможность удаления категории
     */
    public function canBeDeleted(): array
    {
        $reasons = [];
        
        // Проверяем товары
        if ($this->products()->exists()) {
            $reasons[] = 'В категории есть товары';
        }
        
        // Проверяем дочерние категории
        if ($this->children()->exists()) {
            $reasons[] = 'У категории есть подкатегории';
        }
        
        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons
        ];
    }
}