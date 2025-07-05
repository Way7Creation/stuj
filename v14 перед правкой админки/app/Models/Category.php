<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля (ОБНОВЛЕНО с поддержкой подкатегорий)
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',        // НОВОЕ
        'sort_order',       // НОВОЕ  
        'meta_title',       // НОВОЕ
        'meta_description'  // НОВОЕ
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    /**
     * Связь с товарами (многие ко многим)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    /**
     * НОВОЕ: Связь с родительской категорией
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * НОВОЕ: Связь с подкатегориями
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * НОВОЕ: Получить все подкатегории рекурсивно
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * НОВОЕ: Scope - только корневые категории
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * НОВОЕ: Scope - только подкатегории
     */
    public function scopeChild($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * НОВОЕ: Получить полный путь категории
     */
    public function getBreadcrumbsAttribute(): array
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
     * Получить количество товаров в категории (ОБНОВЛЕНО)
     */
    public function getProductsCountAttribute(): int
    {
        $count = $this->products()->count();
        
        // Добавляем товары из подкатегорий
        if ($this->relationLoaded('children')) {
            foreach ($this->children as $child) {
                $count += $child->products_count;
            }
        }
        
        return $count;
    }

    /**
     * НОВОЕ: Проверить, является ли категория корневой
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * НОВОЕ: Проверить, имеет ли категория подкатегории
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * НОВОЕ: Получить уровень вложенности категории
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $category = $this->parent;
        
        while ($category) {
            $depth++;
            $category = $category->parent;
        }
        
        return $depth;
    }
}