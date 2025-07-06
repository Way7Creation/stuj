<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    protected $fillable = [
        'filename',
        'product_id',
        'is_main',
        'sort_order',
        'type'
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Товар, к которому относится изображение
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить URL изображения
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/images/products/' . $this->filename);
    }

    /**
     * Установить как главное изображение
     */
    public function setAsMain(): void
    {
        // Сбрасываем флаг у других изображений товара
        if ($this->product_id) {
            self::where('product_id', $this->product_id)
                ->where('id', '!=', $this->id)
                ->update(['is_main' => false]);
        }
        
        $this->update(['is_main' => true]);
    }
}