<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/ProductAttribute.php
| Описание: НОВАЯ промежуточная модель для связи товаров с атрибутами через значения
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Pivot
{
    /**
     * Указываем таблицу явно
     */
    protected $table = 'product_attributes';

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_value_id',
        'custom_value'
    ];

    /**
     * Отключаем timestamps для pivot таблицы
     */
    public $timestamps = false;

    /**
     * Связь с товаром
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Связь с атрибутом
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Связь со значением атрибута
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    /**
     * Получить отображаемое значение
     * (приоритет: custom_value > attribute_value.value > "Не указано")
     */
    public function getDisplayValueAttribute(): string
    {
        if (!empty($this->custom_value)) {
            return $this->custom_value;
        }

        if ($this->attributeValue) {
            return $this->attributeValue->value;
        }

        return 'Не указано';
    }

    /**
     * Проверить, используется ли кастомное значение
     */
    public function isCustomValue(): bool
    {
        return !empty($this->custom_value);
    }

    /**
     * Проверить, используется ли стандартное значение
     */
    public function isStandardValue(): bool
    {
        return !empty($this->attribute_value_id);
    }
}